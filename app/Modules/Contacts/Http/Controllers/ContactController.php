<?php

namespace App\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\AccessCredentials;
use App\Modules\Contacts\Services\ContactAccessSender;
use App\Modules\Contacts\Services\ContactImporter;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Teamkontakte (Geschäftsstelle): importierte Mailingliste, persönliche Zugänge senden, sperren, Kontakte löschen. */
class ContactController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly ContactImporter $importer,
        private readonly ContactAccessSender $sender,
        private readonly AccessCredentials $credentials,
        private readonly MailSuppressions $suppressions,
        private readonly MailUnsubscribes $unsubscribes,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $state = in_array($request->query('zugang'), ['keiner', 'gesendet', 'benutzt', 'gesperrt'], true) ? (string) $request->query('zugang') : '';

        $query = TeamContact::query()->with('credentials')
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn ($inner) => $inner->where('email', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like));
            })
            ->when($state === 'keiner', fn ($builder) => $builder->whereDoesntHave('credentials', fn ($c) => $c->whereNotNull('sent_at')))
            ->when($state === 'gesendet', fn ($builder) => $builder->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('sent_at')->whereNull('last_used_at')))
            ->when($state === 'benutzt', fn ($builder) => $builder->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('last_used_at')))
            ->when($state === 'gesperrt', fn ($builder) => $builder->whereHas('credentials')->whereDoesntHave('credentials', fn ($c) => $c->valid()))
            ->orderBy('email');

        $page = $query->paginate(self::PER_PAGE)->withQueryString();
        $emails = $page->getCollection()->pluck('email')->all();
        $suppressed = $this->suppressions->among($emails);
        $unsubscribed = $this->unsubscribes->among($emails)['team_mailings'] ?? [];

        return view('contacts.index', [
            'contacts' => $page,
            'suppressed' => $suppressed,
            'unsubscribed' => $unsubscribed,
            'filters' => ['q' => $search !== '' ? $search : null, 'zugang' => $state !== '' ? $state : null],
            'stats' => [
                'total' => TeamContact::query()->count(),
                'without' => TeamContact::query()->whereDoesntHave('credentials', fn ($c) => $c->whereNotNull('sent_at'))->count(),
                'used' => TeamContact::query()->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('last_used_at'))->count(),
            ],
        ]);
    }

    public function importForm(): View
    {
        return view('contacts.import', ['preview' => null, 'list' => '']);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach (ContactImporter::template() as $row) {
                fputcsv($out, $row, ';', '"', '\\');
            }

            fclose($out);
        }, 'mailingliste-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        [$list, $error] = $this->content($request);

        if ($error !== null) {
            return back()->withInput()->withErrors(['list' => $error]);
        }

        return view('contacts.import', ['preview' => $this->importer->preview($list), 'list' => $list]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'list' => ['required', 'string', 'max:2000000'],
            'consent' => ['accepted'],
        ], ['list.required' => 'Es fehlt die Liste. Bitte starten Sie von vorn.', 'consent.accepted' => 'Bitte bestätigen Sie, dass die Einwilligung zur E-Mail-Kommunikation vorliegt.']);

        $result = $this->importer->import((string) $request->input('list'), $this->actor($request));

        if ($result['errors'] !== []) {
            return redirect()->route('contacts.import')->with('import_errors', array_slice($result['errors'], 0, 50))->with('import_error_count', count($result['errors']));
        }

        return redirect()->route('contacts.index')->with('success', "{$result['created']} Adressen neu, {$result['updated']} ergänzt, {$result['skipped']} übersprungen.");
    }

    public function sendAll(Request $request): RedirectResponse
    {
        $sent = 0;
        $blocked = 0;

        TeamContact::query()->whereDoesntHave('credentials', fn ($c) => $c->whereNotNull('sent_at'))->orderBy('id')->each(function (TeamContact $contact) use (&$sent, &$blocked): void {
            $this->sender->queue($contact) ? $sent++ : $blocked++;
        });

        return redirect()->route('contacts.index')->with('success', "{$sent} Zugänge werden versendet".($blocked > 0 ? ", {$blocked} Adressen sind gesperrt oder abgemeldet und bekommen nichts." : '.'));
    }

    public function send(TeamContact $contact): RedirectResponse
    {
        if (! $this->sender->queue($contact)) {
            return redirect()->route('contacts.index')->with('warning', 'Diese Adresse ist gesperrt oder abgemeldet und bekommt nichts.');
        }

        return redirect()->route('contacts.index')->with('success', 'Der Zugang wird versendet.');
    }

    public function revoke(TeamContact $contact): RedirectResponse
    {
        $count = $this->credentials->revokeAll($contact);

        return redirect()->route('contacts.index')->with('success', $count > 0 ? 'Der Zugang wurde gesperrt.' : 'Es gab keinen gültigen Zugang.');
    }

    public function destroy(TeamContact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Der Kontakt wurde gelöscht.');
    }

    /** @return array{0: string, 1: string|null} Inhalt der Liste (Datei oder Textfeld) und eine Fehlermeldung */
    private function content(Request $request): array
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'list' => ['nullable', 'string', 'max:2000000'],
            'consent' => ['accepted'],
        ], ['file.mimes' => 'Erlaubt sind CSV- und Textdateien.', 'file.max' => 'Die Datei darf höchstens 2 MB groß sein.', 'consent.accepted' => 'Bitte bestätigen Sie, dass die Einwilligung zur E-Mail-Kommunikation vorliegt.']);

        $file = $request->file('file');
        $list = $file !== null ? (string) file_get_contents((string) $file->getRealPath()) : (string) $request->input('list', '');

        return trim($list) === '' ? ['', 'Bitte laden Sie eine Datei hoch oder fügen Sie die Adressen in das Feld ein.'] : [$list, null];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
