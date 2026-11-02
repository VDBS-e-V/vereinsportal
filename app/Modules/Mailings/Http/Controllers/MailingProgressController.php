<?php

namespace App\Modules\Mailings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Support\MailGroups;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);
    }

    public function cancel(Request $request, Mailing $mailing): RedirectResponse
    {
        $base = $this->base($request);
        $this->ensureKind($base, $mailing);

        if (! $mailing->isActive()) {
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
