<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ablauf einer Mitgliedschaft: Antrag, Aufnahme oder Ablehnung, Austritt. Jede Änderung steht im Verlauf der Mitgliedschaft
 * (membership_events); die Rolle Vereinsmitglied folgt automatisch (MembershipRoles). Das Austrittsdatum ist der letzte Tag
 * der Mitgliedschaft. Mitgliedschaftsdaten gelangen nicht ins allgemeine Protokoll.
 */
final class Memberships
{
    public function __construct(private readonly MembershipRoles $roles) {}

    /** Erfasst einen Antrag. */
    public function request(Person $person, User $by, ?string $comment = null, ?Carbon $on = null): Membership
    {
        $this->assertNoOpenMembership($person);

        return DB::transaction(function () use ($person, $by, $comment, $on): Membership {
            $membership = Membership::query()->create([
                'person_id' => $person->id,
                'status' => MembershipStatus::Requested,
                'requested_on' => ($on ?? now())->toDateString(),
            ]);

            $this->record($membership, MembershipEventType::Requested, $by, $comment);

            return $membership;
        });
    }

    /** Nimmt eine Person ohne vorherigen Antrag direkt auf (z. B. Altbestand). */
    public function admitDirectly(Person $person, Carbon $startsOn, User $by, ?string $comment = null): Membership
    {
        $this->assertNoOpenMembership($person);

        $membership = DB::transaction(function () use ($person, $startsOn, $by, $comment): Membership {
            $membership = Membership::query()->create([
                'person_id' => $person->id,
                'status' => MembershipStatus::Active,
                'requested_on' => $startsOn->toDateString(),
                'starts_on' => $startsOn->toDateString(),
                'decided_at' => now(),
                'decided_by_user_id' => $by->id,
            ]);

            $this->record($membership, MembershipEventType::AdmittedDirectly, $by, $comment, ['beitritt' => $startsOn->toDateString()]);

            return $membership;
        });

        $this->roles->syncPerson($person);

        return $membership;
    }

    public function admit(Membership $membership, Carbon $startsOn, User $by, ?string $comment = null): void
    {
        if ($membership->status !== MembershipStatus::Requested) {
            throw new DomainException('Nur beantragte Mitgliedschaften können aufgenommen werden.');
        }

        DB::transaction(function () use ($membership, $startsOn, $by, $comment): void {
            $membership->forceFill([
                'status' => MembershipStatus::Active,
                'starts_on' => $startsOn->toDateString(),
                'decided_at' => now(),
                'decided_by_user_id' => $by->id,
            ])->save();

            $this->record($membership, MembershipEventType::Admitted, $by, $comment, ['beitritt' => $startsOn->toDateString()]);
        });

        $this->roles->syncPerson($membership->person);
    }

    public function reject(Membership $membership, string $comment, User $by): void
    {
        if ($membership->status !== MembershipStatus::Requested) {
            throw new DomainException('Nur beantragte Mitgliedschaften können abgelehnt werden.');
        }

        DB::transaction(function () use ($membership, $comment, $by): void {
            $membership->forceFill([
                'status' => MembershipStatus::Rejected,
                'decided_at' => now(),
                'decided_by_user_id' => $by->id,
            ])->save();

            $this->record($membership, MembershipEventType::Rejected, $by, $comment);
        });
    }

    /** Trägt den Austritt ein (letzter Tag der Mitgliedschaft; auch rückwirkend). Liegt er in der Vergangenheit, ist die Mitgliedschaft beendet. */
    public function terminate(Membership $membership, Carbon $endsOn, User $by, ?string $comment = null): void
    {
        if ($membership->effectiveStatus() !== MembershipStatus::Active) {
            throw new DomainException('Nur laufende Mitgliedschaften können beendet werden.');
        }

        if ($membership->starts_on !== null && $endsOn->lt($membership->starts_on)) {
            throw new DomainException('Das Austrittsdatum darf nicht vor dem Beitritt liegen.');
        }

        DB::transaction(function () use ($membership, $endsOn, $by, $comment): void {
            $ended = $endsOn->copy()->startOfDay()->lt(today());

            $membership->forceFill([
                'ends_on' => $endsOn->toDateString(),
                'status' => $ended ? MembershipStatus::Ended : MembershipStatus::Active,
            ])->save();

            $this->record($membership, MembershipEventType::TerminationSet, $by, $comment, ['austritt' => $endsOn->toDateString()]);

            if ($ended) {
                $this->record($membership, MembershipEventType::Ended, $by, null);
            }
        });

        $this->roles->syncPerson($membership->person);
    }

    public function withdrawTermination(Membership $membership, User $by, ?string $comment = null): void
    {
        if (! $membership->isEnding()) {
            throw new DomainException('Für diese Mitgliedschaft ist kein bevorstehender Austritt eingetragen.');
        }

        DB::transaction(function () use ($membership, $by, $comment): void {
            $membership->forceFill(['ends_on' => null])->save();

            $this->record($membership, MembershipEventType::TerminationWithdrawn, $by, $comment);
        });

        $this->roles->syncPerson($membership->person);
    }

    public function note(Membership $membership, string $comment, User $by): void
    {
        $this->record($membership, MembershipEventType::Note, $by, $comment);
    }

    /**
     * Täglicher Abgleich: Mitgliedschaften mit abgelaufenem Austrittsdatum auf „beendet“ setzen und die Rollen nachziehen.
     *
     * @return int Anzahl der beendeten Mitgliedschaften
     */
    public function syncAll(): int
    {
        $ended = 0;

        Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereNotNull('ends_on')
            ->where('ends_on', '<', today())
            ->get()
            ->each(function (Membership $membership) use (&$ended): void {
                DB::transaction(function () use ($membership): void {
                    $membership->forceFill(['status' => MembershipStatus::Ended])->save();
                    $this->record($membership, MembershipEventType::Ended, null, null);
                });

                $ended++;
            });

        Membership::query()
            ->whereIn('status', [MembershipStatus::Active->value, MembershipStatus::Ended->value])
            ->with('person')
            ->get()
            ->each(fn (Membership $membership) => $this->roles->syncPerson($membership->person));

        return $ended;
    }

    /** @param  array<string, mixed>  $data */
    private function record(Membership $membership, MembershipEventType $type, ?User $by, ?string $comment, array $data = []): void
    {
        $membership->events()->create([
            'type' => $type,
            'occurred_at' => now(),
            'actor_user_id' => $by?->id,
            'actor_label' => $by !== null ? AuditLog::label($by) : null,
            'comment' => filled($comment) ? $comment : null,
            'data' => $data === [] ? null : $data,
        ]);
    }

    private function assertNoOpenMembership(Person $person): void
    {
        if (Membership::query()->where('person_id', $person->id)->open()->exists()) {
            throw new DomainException('Diese Person hat bereits eine beantragte oder laufende Mitgliedschaft.');
        }
    }
}
