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

    public const STATES = ['keiner' => 'Noch nicht gesendet', 'gesendet' => 'Gesendet, nicht benutzt', 'benutzt' => 'Benutzt', 'gesperrt' => 'Gesperrt'];

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
        $states = array_values(array_intersect(array_keys(self::STATES), \Illuminate\Support\Arr::wrap($request->query('zugang'))));

        $query = TeamContact::query()->with('credentials')
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn ($inner) => $inner->where('email', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like));
            })
            ->when($states !== [], fn ($builder) => $builder->where(function ($any) use ($states): void {
                foreach ($states as $state) {
                    $any->orWhere(fn ($one) => match ($state) {
                        'keiner' => $one->whereDoesntHave('credentials', fn ($c) => $c->whereNotNull('sent_at')),
                        'gesendet' => $one->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('sent_at')->whereNull('last_used_at')),
                        'benutzt' => $one->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('last_used_at')),
                        default => $one->whereHas('credentials')->whereDoesntHave('credentials', fn ($c) => $c->valid()),
                    });
                }
            }))
            ->orderBy('email');

        $page = $query->paginate(self::PER_PAGE)->withQueryString();
        $emails = $page->getCollection()->pluck('email')->all();
        $suppressed = $this->suppressions->among($emails);
        $unsubscribed = $this->unsubscribes->among($emails)['team_mailings'] ?? [];

        return view('contacts.index', [
            'contacts' => $page,
            'suppressed' => $suppressed,
            'unsubscribed' => $unsubscribed,
            'filters' => ['q' => $search !== '' ? $search : null, 'zugang' => $states],
            'stateOptions' => self::STATES,
            'stats' => [
                'total' => TeamContact::query()->count(),
                'without' => TeamContact::query()->whereDoesntHave('credentials', fn ($c) => $c->whereNotNull('sent_at'))->count(),
                'used' => TeamContact::query()->whereHas('credentials', fn ($c) => $c->valid()->whereNotNull('last_used_at'))->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('contacts.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190', 'unique:team_contacts,email'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'consent' => ['accepted'],
            'send_access' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Bitte geben Sie die E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese Adresse steht schon auf der Liste.',
            'consent.accepted' => 'Bitte bestätigen Sie, dass die Einwilligung zur E-Mail-Kommunikation vorliegt.',
        ]);

        $contact = TeamContact::query()->create(array_filter([
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], fn ($value): bool => filled($value)) + ['consent_confirmed_at' => now()]);

        \App\Modules\Audit\Support\AuditLog::record(\App\Modules\Audit\Enums\AuditEvent::ContactsImported, $this->actor($request), null, ['neu' => 1, 'ergaenzt' => 0, 'uebersprungen' => 0]);

        if ($request->boolean('send_access')) {
            return redirect()->route('contacts.index')->with($this->sender->queue($contact) ? 'success' : 'warning', $this->sender->canMail($contact) ? 'Die Adresse wurde hinzugefügt, der Zugang wird versendet.' : 'Die Adresse wurde hinzugefügt. Sie ist gesperrt oder abgemeldet und bekommt keinen Zugang.');
        }

        return redirect()->route('contacts.index')->with('success', 'Die Adresse wurde hinzugefügt.');
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

    /** Zugänge an die angehakten Adressen senden. */
    public function sendSelected(Request $request): RedirectResponse
    {
        $contacts = $this->selected($request);
        $sent = 0;
        $blocked = 0;

        foreach ($contacts as $contact) {
            $this->sender->queue($contact) ? $sent++ : $blocked++;
        }

        return redirect()->back()->with('success', "{$sent} Zugänge werden versendet".($blocked > 0 ? ", {$blocked} Adressen sind gesperrt oder abgemeldet und bekommen nichts." : '.'));
    }

    /** Zugänge der angehakten Adressen sperren. */
    public function revokeSelected(Request $request): RedirectResponse
    {
        $count = 0;

        foreach ($this->selected($request) as $contact) {
            $count += $this->credentials->revokeAll($contact) > 0 ? 1 : 0;
        }

        return redirect()->back()->with('success', "{$count} Zugänge wurden gesperrt.");
    }

    /** Auswahl löschen: erst eine Bestätigungsseite mit den Adressen, dann (mit confirmed=1) das Löschen. */
    public function deleteSelected(Request $request): View|RedirectResponse
    {
        $contacts = $this->selected($request);

        if (! $request->boolean('confirmed')) {
            return view('contacts.delete-selected', ['contacts' => $contacts]);
        }

        $count = $contacts->count();
        $contacts->each->delete();

        return redirect()->route('contacts.index')->with('success', $count === 1 ? '1 Kontakt wurde gelöscht.' : "{$count} Kontakte wurden gelöscht.");
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, TeamContact> */
    private function selected(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['integer']], ['ids.required' => 'Bitte haken Sie mindestens eine Adresse an.', 'ids.min' => 'Bitte haken Sie mindestens eine Adresse an.']);

        return TeamContact::query()->whereIn('id', $data['ids'])->get();
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
