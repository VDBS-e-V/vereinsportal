<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Html\RichText;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Mitteilungen der Koordination an Teamende zu anstehenden Seminaren: Vorlage der Geschäftsstelle plus eigener Textabschnitt. */
class TeamMailingController extends Controller
{
    public const AUDIENCES = ['all' => 'Alle Teamenden', 'applied' => 'Teamende mit Interesse an diesem Seminar', 'confirmed' => 'Teamende im Team dieses Seminars'];

    public function __construct(private readonly Mailings $mailings, private readonly MailRecipients $recipients, private readonly MailTemplates $templates) {}

    public function index(): View
    {
        return view('mailings.team-index', [
            'mailings' => Mailing::query()->where('kind', Mailing::KIND_TEAM)->with(['sentBy.person', 'seminar'])->orderByDesc('id')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $seminar = $this->seminars()->firstWhere('id', (int) old('seminar_id', $request->query('seminar')));
        $counts = [];

        foreach (array_keys(self::AUDIENCES) as $audience) {
            $counts[$audience] = $this->recipients->count(Mailing::KIND_TEAM, $seminar, $audience);
        }

        return view('mailings.team-form', [
            'seminars' => $this->seminars()->mapWithKeys(fn (Seminar $s): array => [$s->id => $s->title.($s->partner ? ' – '.$s->partner->name : '')])->all(),
            'audiences' => self::AUDIENCES,
            'counts' => $counts,
            'selected' => $seminar?->id,
            'template' => $this->templates->get(MailTemplates::TEAM_SEMINAR),
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        [$data, $seminar] = $this->validated($request);
        $preview = $this->mailings->previewTeam($seminar, $data['own_text'] ?? '', $this->user());

        return redirect()->route('mailings.team.create')->withInput($request->all())->with('preview', $preview);
    }

    public function test(Request $request): RedirectResponse
    {
        [$data, $seminar] = $this->validated($request);
        $this->mailings->sendTeamTest($seminar, $data['own_text'] ?? '', $this->user());

        return redirect()->route('mailings.team.create')->withInput($request->all())->with('success', 'Die Testmail wurde an Ihre Adresse gesendet.');
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $seminar] = $this->validated($request);

        try {
            $mailing = $this->mailings->sendTeam($seminar, $data['audience'], $data['own_text'] ?? '', $this->user());
        } catch (DomainException $exception) {
            return redirect()->route('mailings.team.create')->withInput($request->all())->withErrors(['audience' => $exception->getMessage()]);
        }

        $note = $mailing->failed_count > 0 ? ' '.$mailing->failed_count.' Zustellungen sind fehlgeschlagen (siehe Fehlerprotokoll).' : '';

        return redirect()->route('mailings.team.index')->with('success', 'Die Mitteilung wurde an '.$mailing->recipients_count.' Teamende gesendet.'.$note);
    }

    /** Seminare, zu denen Teamende angeschrieben werden können (in Planung, terminiert oder bestätigt). @return \Illuminate\Support\Collection<int, Seminar> */
    private function seminars()
    {
        return Seminar::query()->whereIn('status', [SeminarStatus::Planning->value, SeminarStatus::Scheduled->value, SeminarStatus::Confirmed->value])->with('partner')->orderBy('title')->get();
    }

    /** @return array{0: array<string, mixed>, 1: Seminar} */
    private function validated(Request $request): array
    {
        $request->merge(['own_text' => RichText::normalize($request->input('own_text'))]);
        $ids = $this->seminars()->pluck('id')->all();

        $data = $request->validate([
            'seminar_id' => ['required', Rule::in($ids)],
            'audience' => ['required', Rule::in(array_keys(self::AUDIENCES))],
            'own_text' => ['nullable', 'string', 'max:10000'],
        ], [
            'seminar_id.required' => 'Bitte wählen Sie ein Seminar.',
            'seminar_id.in' => 'Zu diesem Seminar können keine Mitteilungen gesendet werden.',
            'audience.required' => 'Bitte wählen Sie, wer die Mitteilung bekommt.',
            'own_text.max' => 'Der Textabschnitt ist zu lang.',
        ]);

        return [$data, Seminar::query()->findOrFail((int) $data['seminar_id'])];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
