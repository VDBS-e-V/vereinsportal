<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailGroups;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Fortschritt eines Schreibens (Vorstand, Geschäftsstelle, Koordination): Status, Zähler, fehlgeschlagene Zustellungen, Abbrechen und erneut versuchen.
 * Eine Klasse für alle drei Bereiche. Das Routenpräfix (mailings.board, mailings.office, mailings.team) bestimmt Rücksprung und erlaubte Art des Schreibens.
 */
class MailingProgressController extends Controller
{
    public function __construct(private readonly Mailings $mailings) {}

    public function show(Request $request, Mailing $mailing): View
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);

        if ($mailing->isPreparation()) {
            return $this->showPreparation($base, $mailing);
        }

        $counts = $mailing->deliveries()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = max(1, (int) $mailing->recipients_count);
        $done = (int) ($counts[MailingDelivery::SENT] ?? 0) + (int) ($counts[MailingDelivery::FAILED] ?? 0) + (int) ($counts[MailingDelivery::SKIPPED] ?? 0);
        $detailsKept = $mailing->deliveries()->exists();
        $canSeeAddresses = $mailing->addressesVisibleTo($this->user());

        return view('mailings.show', [
            'mailing' => $mailing->load(['sentBy.person', 'seminar']),
            'base' => $base,
            'area' => $base === 'mailings.team' ? 'Koordination' : ($base === 'mailings.board' ? 'Vorstand' : 'Geschäftsstelle'),
            'counts' => $counts,
            'percent' => (int) min(100, round($done / $total * 100)),
            'groupLabels' => $mailing->groups ? MailGroups::labels($mailing->groups) : null,
            'detailsKept' => $detailsKept,
            'failed' => $canSeeAddresses ? $mailing->deliveries()->where('status', MailingDelivery::FAILED)->orderBy('id')->limit(100)->get() : collect(),
            'canSeeAddresses' => $canSeeAddresses,
            'hasCertificate' => $canSeeAddresses && in_array($base, ['mailings.board', 'mailings.office'], true),
            'unreachable' => $this->unreachable($mailing),
        ]);
    }

    /** Mitglieder, die ein Schreiben an die Gruppe Mitglieder nicht erreicht hat. Nur für die, die Mitglieder anschreiben dürfen, und erst nach dem Versand. */
    private function unreachable(Mailing $mailing): ?array
    {
        if (! in_array('members', (array) $mailing->groups, true) || $mailing->isActive() || ! $this->user()->can('mailings.members')) {
            return null;
        }

        return app(MailRecipients::class)->unreachableMembers($mailing);
    }

    /** Versandbescheinigung: Beleg über Zeitpunkt, Text und Zahlen des Versands, als Druckansicht ohne Navigation. */
    public function certificate(Request $request, Mailing $mailing): View
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);
        abort_if($mailing->isPreparation(), 404);
        abort_unless($mailing->addressesVisibleTo($this->user()), 403);

        $mailing->load(['sentBy.person', 'approvedBy.person']);
        $recipients = (int) $mailing->recipients_count;
        $excluded = (array) (($mailing->values ?? [])['excluded'] ?? []);

        return view('mailings.certificate', [
            'mailing' => $mailing,
            'base' => $base,
            'groupLabels' => $mailing->groups ? MailGroups::labels($mailing->groups) : null,
            'meeting' => $this->mailings->meetingValues((array) (($mailing->payload ?? [])['meeting'] ?? [])),
            'recipients' => $recipients,
            'sent' => (int) $mailing->sent_count,
            'failed' => (int) $mailing->failed_count,
            'open' => max(0, $recipients - (int) $mailing->sent_count - (int) $mailing->failed_count),
            'unsubscribed' => $excluded['unsubscribed'] ?? null,
            'suppressed' => $excluded['suppressed'] ?? null,
            'sender' => $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? 'unbekannt',
            'approver' => $mailing->approvedBy ? ($mailing->approvedBy->person?->fullName() ?? $mailing->approvedBy->email) : null,
            'issuedAt' => now(),
        ]);
    }

    /** Vorbereitung: Entwurf, zur Freigabe vorgelegt oder geplant. Entwürfe sehen nur ihre Autorinnen, vorgelegte auch der Vorstand. */
    private function showPreparation(string $base, Mailing $mailing): View
    {
        $user = $this->user();
        $isAuthor = $user->id === $mailing->sent_by_user_id;
        $canApprove = $user->can('mailings.approve');

        abort_unless($isAuthor || ($canApprove && $mailing->status !== Mailing::STATUS_DRAFT) || $mailing->status === Mailing::STATUS_SCHEDULED, 404);

        $mailing->load(['sentBy.person', 'approvedBy.person']);

        return view('mailings.preparation', [
            'mailing' => $mailing,
            'base' => $base,
            'area' => $base === 'mailings.board' ? 'Vorstand' : 'Geschäftsstelle',
            'preview' => $this->mailings->previewMailing($mailing, $user),
            'groupLabels' => $mailing->groups ? MailGroups::labels($mailing->groups) : null,
            'recipientsNow' => count(app(MailRecipients::class)->groups((array) $mailing->groups, (bool) $mailing->mandatory)),
            'isAuthor' => $isAuthor,
            'notice' => $this->mailings->noticeWarning((array) $mailing->groups, (array) (($mailing->payload ?? [])['meeting'] ?? []), $mailing->send_at),
            'canApprove' => $canApprove && ! $isAuthor && $mailing->status === Mailing::STATUS_PENDING,
        ]);
    }

    public function approve(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);
        Gate::authorize('mailings.approve');

        try {
            $approved = $this->mailings->approve($mailing, $this->user());
        } catch (DomainException $exception) {
            return redirect()->route($base.'.show', $mailing)->with('error', $exception->getMessage());
        }

        return redirect()->route($base.'.show', $approved)->with('success', 'Freigegeben. '.$this->mailings->flashMessage($approved));
    }

    public function reject(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);
        Gate::authorize('mailings.approve');

        $data = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Bitte nennen Sie der Autorin oder dem Autor einen Grund.']);

        try {
            $this->mailings->reject($mailing, $this->user(), $data['note']);
        } catch (DomainException $exception) {
            return redirect()->route($base.'.show', $mailing)->with('error', $exception->getMessage());
        }

        return redirect()->route($base.'.index')->with('success', 'Das Schreiben wurde mit Ihrer Begründung als Entwurf zurückgegeben.');
    }

    /** Zurückziehen: die Autorin oder der Autor zieht ein vorgelegtes oder geplantes Schreiben zurück in den Entwurf. */
    public function withdraw(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);
        abort_unless($this->user()->id === $mailing->sent_by_user_id || $this->user()->can('mailings.approve'), 403);

        try {
            $this->mailings->withdraw($mailing, $this->user());
        } catch (DomainException $exception) {
            return redirect()->route($base.'.show', $mailing)->with('error', $exception->getMessage());
        }

        return redirect()->route($base.'.create', ['entwurf' => $mailing->id])->with('success', 'Das Schreiben ist wieder ein Entwurf.');
    }

    public function destroy(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);
        abort_unless($this->user()->id === $mailing->sent_by_user_id, 403);

        try {
            $this->mailings->deleteDraft($mailing);
        } catch (DomainException $exception) {
            return redirect()->route($base.'.show', $mailing)->with('error', $exception->getMessage());
        }

        return redirect()->route($base.'.index')->with('success', 'Der Entwurf wurde gelöscht.');
    }

    public function cancel(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);

        if (! $mailing->isActive() && $mailing->status !== Mailing::STATUS_SCHEDULED) {
            return redirect()->route($base.'.show', $mailing)->with('error', 'Das Schreiben ist schon abgeschlossen.');
        }

        $this->mailings->cancel($mailing);

        return redirect()->route($base.'.show', $mailing)->with('success', 'Der Versand wurde abgebrochen. Bereits gesendete Nachrichten bleiben gesendet.');
    }

    public function retry(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);

        try {
            $count = $this->mailings->retryFailed($mailing);
        } catch (DomainException $exception) {
            return redirect()->route($base.'.show', $mailing)->with('error', $exception->getMessage());
        }

        return redirect()->route($base.'.show', $mailing)->with('success', $count.' fehlgeschlagene Zustellungen werden noch einmal versucht.');
    }

    /** Die Seiten des Bereichs Koordination zeigen nur Mitteilungen an Teamende, die der anderen Bereiche nur Schreiben an Gruppen. */
    private function ensureKind(string $base, Mailing $mailing): void
    {
        $team = $base === 'mailings.team';

        abort_unless($team ? $mailing->kind === Mailing::KIND_TEAM : in_array($mailing->kind, [Mailing::KIND_GENERAL, Mailing::KIND_MEMBERS], true), 404);
    }

    private function base(Request $request): string
    {
        return Str::beforeLast((string) $request->route()?->getName(), '.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
