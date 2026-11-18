<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Services\SeminarNextcloud;
use App\Modules\Seminars\Services\SeminarNotifications;
use App\Modules\Seminars\Services\SeminarWorkflow;
use App\Modules\Seminars\Services\TeamSelection;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Koordination: freigegebene Seminare planen, ausschreiben und absagen. */
class PlanSeminarController extends Controller
{
    public const SESSION_ROWS = 8;

    public function __construct(private readonly SeminarWorkflow $workflow, private readonly TeamSelection $selection, private readonly SeminarNotifications $notifications) {}

    public function index(Request $request): View
    {
        $status = SeminarStatus::tryFrom((string) $request->query('status', ''));
        $coordination = $request->integer('coordination') ?: null;
        $hidden = [SeminarStatus::Request, SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Rejected];
        $visible = array_values(array_filter(SeminarStatus::cases(), fn (SeminarStatus $case): bool => ! in_array($case, $hidden, true)));

        $seminars = Seminar::query()->with(['partner', 'coordination', 'sessions'])
            ->whereIn('status', array_map(fn (SeminarStatus $case): string => $case->value, $visible))
            ->when($status, fn ($query, SeminarStatus $value) => $query->where('status', $value->value))
            ->when($coordination, fn ($query, int $id) => $query->where('coordination_id', $id))
            ->orderByDesc('id')->get();

        return view('seminars.plan-index', [
            'seminars' => $seminars,
            'status' => $status?->value,
            'coordination' => $coordination,
            'statusOptions' => collect($visible)->mapWithKeys(fn (SeminarStatus $case): array => [$case->value => $case->label()])->all(),
            'coordinationOptions' => Coordination::query()->ordered()->pluck('name', 'id')->all(),
        ]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        abort_if(in_array($seminar->status, [SeminarStatus::Request, SeminarStatus::InReview, SeminarStatus::QuoteSent, SeminarStatus::Rejected], true), 404);

        return view('seminars.plan-show', [
            'seminar' => $seminar->load(['partner', 'coordination', 'sessions', 'events.actor', 'applications.user.person']),
            'rows' => self::SESSION_ROWS,
            'canCancel' => $this->workflow->can($seminar, SeminarStatus::Cancelled, $this->user($request)),
        ]);
    }

    public function plan(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'team_target' => ['required', 'integer', 'min:1', 'max:50'],
            'sessions' => ['nullable', 'array', 'max:'.self::SESSION_ROWS],
        ], ['team_target.required' => 'Bitte geben Sie die Sollzahl des Teams an.', 'team_target.integer' => 'Bitte geben Sie die Sollzahl als ganze Zahl an.']);

        $sessions = $this->parseSessions((array) ($data['sessions'] ?? []));

        try {
            $this->workflow->plan($seminar, $sessions, (int) $data['team_target'], $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['plan' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Termine und Sollzahl sind gespeichert.');
    }

    public function tender(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['application_deadline' => ['required', 'date']], ['application_deadline.required' => 'Bitte geben Sie die Bewerbungsfrist an.']);

        try {
            $this->workflow->tender($seminar, Carbon::parse($data['application_deadline']), $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['application_deadline' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Das Seminar ist für das Team ausgeschrieben.');
    }

    /** Zusage, Absage oder Zurücksetzen für eine Meldung (Entwurf, es wird nichts gesendet). */
    public function decide(Request $request, Seminar $seminar, SeminarApplication $application): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['confirm', 'decline', 'reset'])]]);

        return $this->team($seminar, fn () => $this->selection->draft($seminar, $application, $data['decision'] === 'reset' ? null : $data['decision'], $this->user($request)), 'Der Entwurf ist gespeichert. Gesendet wird erst mit „Team bestätigen“.');
    }

    public function confirmTeam(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['lead' => ['required', 'integer'], 'acknowledge' => ['nullable', 'boolean']], ['lead.required' => 'Bitte bestimmen Sie die Seminarleitung.']);

        return $this->team($seminar, fn () => $this->selection->confirm($seminar, $this->user($request), (int) $data['lead'], $request->boolean('acknowledge')), 'Das Team ist bestätigt. Zusagen, Absagen und Wartelistenplätze sind unterwegs.');
    }

    public function promote(Request $request, Seminar $seminar, SeminarApplication $application): RedirectResponse
    {
        return $this->team($seminar, fn () => $this->selection->promote($seminar, $application, $this->user($request)), 'Die Person ist nachgerückt und hat die Zusage erhalten.');
    }

    public function drop(Request $request, Seminar $seminar, SeminarApplication $application): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']], ['reason.required' => 'Bitte geben Sie eine Begründung an.']);

        return $this->team($seminar, fn () => $this->selection->drop($seminar, $application, $this->user($request), $data['reason']), 'Die Person ist aus dem Team ausgeschieden.');
    }

    public function lead(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['lead' => ['required', 'integer']], ['lead.required' => 'Bitte wählen Sie die Seminarleitung.']);

        return $this->team($seminar, fn () => $this->selection->setLead($seminar, (int) $data['lead'], $this->user($request)), 'Die Seminarleitung ist geändert.');
    }

    /** Termin für das Onboarding festlegen und das Team einladen. */
    public function onboarding(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'onboarding_at' => ['required', 'date', 'after:now'],
            'onboarding_link' => ['nullable', 'url:https', 'max:500'],
        ], ['onboarding_at.required' => 'Bitte geben Sie Datum und Uhrzeit an.', 'onboarding_at.after' => 'Der Termin muss in der Zukunft liegen.', 'onboarding_link.url' => 'Bitte geben Sie einen Link mit https:// an.']);

        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            return back()->withErrors(['team' => 'Das Onboarding lässt sich nach der Teambestätigung festlegen.']);
        }

        $seminar->forceFill(['onboarding_at' => Carbon::parse($data['onboarding_at']), 'onboarding_link' => $data['onboarding_link'] ?? null])->save();
        $this->workflow->log($seminar, 'onboarding', $this->user($request), null, null, 'Onboarding am '.$seminar->onboarding_at->format('d.m.Y, H:i').' Uhr');
        $this->notifications->onboardingInvitation($seminar);

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Der Onboarding-Termin ist gespeichert und das Team ist eingeladen.');
    }

    /** Briefing an die Schule senden (Ablauf, Raum, Material). */
    public function briefing(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:3000']], ['text.required' => 'Bitte schreiben Sie das Briefing.']);

        if (! in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true)) {
            return back()->withErrors(['team' => 'Das Briefing lässt sich nach der Teambestätigung senden.']);
        }

        $this->notifications->briefing($seminar->loadMissing('partner'), $data['text']);
        $seminar->forceFill(['briefing_sent_at' => now()])->save();
        $this->workflow->log($seminar, 'briefing', $this->user($request), null, null, 'Briefing an die Schule gesendet');

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Das Briefing ist an die Schule gesendet.');
    }

    /** Bericht prüfen: zurück an die Seminarleitung (Begründung Pflicht). */
    public function returnReport(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']], ['comment.required' => 'Bitte geben Sie an, was fehlt oder zu ändern ist.']);

        return $this->team($seminar, fn () => $this->workflow->transition($seminar, SeminarStatus::Held, $this->user($request), $data['comment']), 'Der Bericht ist zur Überarbeitung an die Seminarleitung zurückgegeben.');
    }

    /** Bericht und Ergebnislink an die Schule senden. */
    public function sendResults(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'results_url' => ['nullable', 'url:https', 'max:500'],
            'results_expires_on' => ['required', 'date'],
        ], ['results_url.url' => 'Bitte geben Sie einen Link mit https:// an.', 'results_expires_on.required' => 'Bitte geben Sie an, bis wann der Link erreichbar ist.']);

        return $this->team($seminar, fn () => $this->workflow->sendResults($seminar, $data['results_url'] ?? null, Carbon::parse($data['results_expires_on']), $this->user($request)), 'Bericht und Ergebnislink sind an die Schule gesendet.');
    }

    public function extendResults(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['results_expires_on' => ['required', 'date']], ['results_expires_on.required' => 'Bitte geben Sie das neue Datum an.']);

        return $this->team($seminar, fn () => $this->workflow->extendResults($seminar, Carbon::parse($data['results_expires_on']), $this->user($request)), 'Der Ergebnislink ist verlängert.');
    }

    /** Nextcloud-Ordner jetzt anlegen (nochmal versuchen) oder vermerken, dass er von Hand angelegt wird. */
    public function folder(Request $request, Seminar $seminar, SeminarNextcloud $nextcloud): RedirectResponse
    {
        $data = $request->validate(['mode' => ['required', Rule::in(['create', 'manual'])]]);

        if ($data['mode'] === 'manual') {
            $seminar->forceFill(['nextcloud_manual' => true])->save();
            $this->workflow->log($seminar, 'nextcloud', $this->user($request), null, null, 'Der Ordner wird von Hand angelegt');

            return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Vermerkt: Der Ordner wird von Hand angelegt.');
        }

        if (! $nextcloud->enabled()) {
            return back()->withErrors(['team' => 'Die Nextcloud ist nicht eingerichtet. Legen Sie den Ordner von Hand an.']);
        }

        return $nextcloud->ensureFolders($seminar)
            ? redirect()->route('seminars.plan.show', $seminar)->with('success', 'Der Nextcloud-Ordner ist angelegt.')
            : back()->withErrors(['team' => (string) $seminar->fresh()?->nextcloud_error]);
    }

    public function complete(Request $request, Seminar $seminar): RedirectResponse
    {
        return $this->team($seminar, fn () => $this->workflow->complete($seminar, $this->user($request)), 'Das Seminar ist abgeschlossen.');
    }

    /** Material und andere Dateien zum Seminar hochladen (für Team und Druckplaner). */
    public function upload(Request $request, Seminar $seminar, SeminarDocuments $documents): RedirectResponse
    {
        $data = $request->validate(['file' => ['required', 'file'], 'kind' => ['required', Rule::in(['material', 'other'])], 'title' => ['nullable', 'string', 'max:200']], ['file.required' => 'Bitte wählen Sie eine Datei.']);

        return $this->team($seminar, fn () => $documents->add($seminar, $request->file('file'), $data['kind'], $data['title'] ?? null, $this->user($request)), 'Die Datei ist gespeichert.');
    }

    public function prepare(Request $request, Seminar $seminar): RedirectResponse
    {
        return $this->team($seminar, fn () => $this->workflow->prepare($seminar, $this->user($request)), 'Das Seminar ist vorbereitet.');
    }

    private function team(Seminar $seminar, callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return back()->withErrors(['team' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', $success);
    }

    public function cancel(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']], ['comment.required' => 'Bitte geben Sie eine Begründung an.']);

        try {
            $this->workflow->transition($seminar, SeminarStatus::Cancelled, $this->user($request), $data['comment']);
        } catch (DomainException $exception) {
            return back()->withErrors(['comment' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.plan.show', $seminar)->with('success', 'Das Seminar ist abgesagt.');
    }

    /**
     * Termine aus dem Formular (Datum, von, bis, Ort). Leere Zeilen entfallen.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{starts_at: Carbon, ends_at: ?Carbon, location: ?string}>
     */
    private function parseSessions(array $rows): array
    {
        $sessions = [];

        foreach ($rows as $index => $row) {
            $row = (array) $row;
            $date = trim((string) ($row['date'] ?? ''));
            $from = trim((string) ($row['from'] ?? ''));
            $to = trim((string) ($row['to'] ?? ''));
            $location = trim((string) ($row['location'] ?? ''));

            if ($date === '' && $from === '' && $to === '' && $location === '') {
                continue;
            }

            $start = $date !== '' && preg_match('/^\d{2}:\d{2}$/', $from) === 1 ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$from) : null;

            if (! $start instanceof Carbon) {
                throw ValidationException::withMessages(['sessions.'.$index.'.date' => 'Bitte geben Sie Datum und Beginn an (Beginn zum Beispiel 09:00).']);
            }

            $end = null;

            if ($to !== '') {
                $end = preg_match('/^\d{2}:\d{2}$/', $to) === 1 ? Carbon::createFromFormat('Y-m-d H:i', $date.' '.$to) : null;

                if (! $end instanceof Carbon || $end->lessThanOrEqualTo($start)) {
                    throw ValidationException::withMessages(['sessions.'.$index.'.to' => 'Das Ende muss nach dem Beginn liegen (zum Beispiel 13:30).']);
                }
            }

            $sessions[] = ['starts_at' => $start, 'ends_at' => $end, 'location' => $location === '' ? null : mb_substr($location, 0, 200)];
        }

        usort($sessions, fn (array $a, array $b): int => $a['starts_at']->getTimestamp() <=> $b['starts_at']->getTimestamp());

        return $sessions;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
