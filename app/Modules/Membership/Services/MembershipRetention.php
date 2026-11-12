<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Aufbewahrungsfristen für Mitgliedschaftsdaten (Konfiguration: config('portal.membership.retention')):
 *  - unbestätigte Online-Anträge: Konto, Person und Antrag werden gelöscht, wenn die E-Mail-Adresse nicht bestätigt wurde;
 *  - abgelehnte Anträge: Mitgliedschaft samt Dokumenten, Einwilligungen und Verlauf wird nach der Frist gelöscht (ein Konto
 *    der Person bleibt bestehen);
 *  - beendete Mitgliedschaften: Löschung nach der Frist, nur wenn eine Frist gesetzt ist (Standard: aus, der Verein entscheidet).
 * Ohne $dryRun wird gelöscht; sonst nur gezählt.
 */
final class MembershipRetention
{
    /** @return array{unverified: int, rejected: int, ended: int} */
    public function prune(bool $dryRun = false): array
    {
        return [
            'unverified' => $this->pruneUnverified((int) config('portal.membership.retention.unverified_days'), $dryRun),
            'rejected' => $this->pruneByStatus(MembershipStatus::Rejected, config('portal.membership.retention.rejected_days'), $dryRun),
            'ended' => $this->pruneByStatus(MembershipStatus::Ended, config('portal.membership.retention.ended_days'), $dryRun),
        ];
    }

    private function pruneUnverified(int $days, bool $dryRun): int
    {
        if ($days < 1) {
            return 0;
        }

        $memberships = Membership::query()
            ->where('source', 'online')
            ->where('status', MembershipStatus::Requested->value)
            ->where('created_at', '<', now()->subDays($days))
            ->whereHas('person.user', fn ($user) => $user->where('status', UserStatus::PendingVerification->value)->whereNull('email_verified_at')->whereNull('last_login_at'))
            ->with('person.user')
            ->get();

        if (! $dryRun) {
            foreach ($memberships as $membership) {
                $person = $membership->person;
                $user = $person->user;

                DB::transaction(function () use ($membership, $person, $user): void {
                    $this->deleteMembership($membership);

                    if ($user instanceof User) {
                        $user->delete();
                    }

                    $person->delete();
                });
            }
        }

        return $memberships->count();
    }

    private function pruneByStatus(MembershipStatus $status, mixed $days, bool $dryRun): int
    {
        if (! is_numeric($days) || (int) $days < 1) {
            return 0;
        }

        $cutoff = now()->subDays((int) $days);

        $query = Membership::query()->where(function ($old) use ($status, $cutoff): void {
            $old->where('status', $status->value)->where(fn ($date) => $status === MembershipStatus::Ended
                ? $date->where('ends_on', '<', $cutoff->toDateString())
                : $date->where('decided_at', '<', $cutoff));

            // Aktive Mitgliedschaften mit längst abgelaufenem Austritt zählen als beendet
            if ($status === MembershipStatus::Ended) {
                $old->orWhere(fn ($active) => $active->where('status', MembershipStatus::Active->value)->where('ends_on', '<', $cutoff->toDateString()));
            }
        });

        $memberships = $query->get();

        if (! $dryRun) {
            foreach ($memberships as $membership) {
                DB::transaction(fn () => $this->deleteMembership($membership));
            }
        }

        return $memberships->count();
    }

    /** Löscht Mitgliedschaft (Verlauf, Dokumentenzeilen und Einwilligungen folgen per Cascade) und die Dateien im privaten Speicher. */
    private function deleteMembership(Membership $membership): void
    {
        Storage::disk(MembershipDocuments::DISK)->deleteDirectory('membership-documents/'.$membership->id);

        $membership->delete();
    }
}
