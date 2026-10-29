<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ablauf einer Mitgliedschaft: Antrag, Aufnahme oder Ablehnung, Austritt. Jede Änderung steht im Verlauf der Mitgliedschaft
 * (membership_events); die Rolle Vereinsmitglied folgt automatisch (MembershipRoles). Das Austrittsdatum ist der letzte Tag
 * der Mitgliedschaft. Mitgliedschaftsdaten gelangen nicht ins allgemeine Protokoll.
 */
final class Memberships
{
    public function __construct(
        private readonly MembershipRoles $roles,
        private readonly MembershipHistory $history,
        private readonly MembershipDocuments $documents,
    ) {}

    /**
     * Erfasst eine Mitgliedschaft manuell, ohne digitale Person: Antrag oder Altbestand als Dokument (Upload), mit Name,
     * optional Geburtsdatum und E-Mail. Mit $startsOn wird die Person direkt aufgenommen, sonst ist es ein Antrag.
     *
     * @param  array{first_name: string, last_name: string, birth_date?: ?string, email?: ?string}  $identity
     */
    public function createManual(array $identity, UploadedFile $file, string $kind, User $by, ?Carbon $startsOn = null, ?string $comment = null): Membership
    {
        $email = filled($identity['email'] ?? null) ? mb_strtolower(trim((string) $identity['email'])) : null;
        $birth = filled($identity['birth_date'] ?? null) ? Carbon::parse($identity['birth_date']) : null;

        $this->assertNoOpenDuplicate($identity['first_name'], $identity['last_name'], $birth, $email);

        $membership = DB::transaction(function () use ($identity, $email, $birth, $startsOn, $by, $comment): Membership {
            $membership = Membership::query()->create([
                'first_name' => trim($identity['first_name']),
                'last_name' => trim($identity['last_name']),
                'birth_date' => $birth?->toDateString(),
                'email' => $email,
                'source' => 'manual',
                'status' => $startsOn !== null ? MembershipStatus::Active : MembershipStatus::Requested,
                'requested_on' => ($startsOn ?? now())->toDateString(),
                'starts_on' => $startsOn?->toDateString(),
                'decided_at' => $startsOn !== null ? now() : null,
                'decided_by_user_id' => $startsOn !== null ? $by->id : null,
            ]);

            $this->history->record(
                $membership,
                $startsOn !== null ? MembershipEventType::AdmittedDirectly : MembershipEventType::Requested,
                $by,
                $comment,
                $startsOn !== null ? ['beitritt' => $startsOn->toDateString()] : [],
            );

            return $membership;
        });

        $this->documents->store($membership, $file, $kind, $by);

        return $membership;
    }

    /** Antrag einer Person, die sich online registriert hat (ohne angemeldeten Akteur). */
    public function requestOnline(Person $person): Membership
    {
        return DB::transaction(function () use ($person): Membership {
            $membership = Membership::query()->create([
                'person_id' => $person->id,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'birth_date' => $person->birth_date?->toDateString(),
                'email' => $person->email,
                'source' => 'online',
                'status' => MembershipStatus::Requested,
                'requested_on' => now()->toDateString(),
            ]);

            $this->history->record($membership, MembershipEventType::Requested, null, null, [], 'Online-Antrag');

            return $membership;
        });
    }

    /** Eine beantragte oder laufende Mitgliedschaft mit gleichem Namen und Geburtsdatum oder gleicher E-Mail-Adresse? */
    public function hasOpenDuplicate(string $first, string $last, ?Carbon $birth, ?string $email): bool
    {
        $query = Membership::query()->open()->where(function (Builder $match) use ($first, $last, $birth, $email): void {
            $any = false;

            if ($birth !== null) {
                $any = true;
                $match->where(fn (Builder $name) => $name
                    ->where(fn (Builder $own) => $own->where('first_name', trim($first))->where('last_name', trim($last))->whereDate('birth_date', $birth->toDateString()))
                    ->orWhereHas('person', fn (Builder $person) => $person->where('first_name', trim($first))->where('last_name', trim($last))->whereDate('birth_date', $birth->toDateString())));
            }

            if ($email !== null) {
                $match->{$any ? 'orWhere' : 'where'}(fn (Builder $mail) => $mail->where('email', $email)->orWhereHas('person', fn (Builder $person) => $person->where('email', $email)));
                $any = true;
            }

            if (! $any) {
                $match->whereRaw('1 = 0');
            }
        });

        return $query->exists();
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

        $this->syncRoles($membership);
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

        $this->syncRoles($membership);
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

        $this->syncRoles($membership);
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
            ->each(fn (Membership $membership) => $this->syncRoles($membership));

        return $ended;
    }

    private function syncRoles(Membership $membership): void
    {
        if ($membership->person !== null) {
            $this->roles->syncPerson($membership->person);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function record(Membership $membership, MembershipEventType $type, ?User $by, ?string $comment, array $data = []): void
    {
        $this->history->record($membership, $type, $by, $comment, $data, $by === null ? 'automatisch' : null);
    }

    private function assertNoOpenDuplicate(string $first, string $last, ?Carbon $birth, ?string $email): void
    {
        if ($this->hasOpenDuplicate($first, $last, $birth, $email)) {
            throw new DomainException('Für diese Person gibt es bereits eine beantragte oder laufende Mitgliedschaft (gleicher Name mit Geburtsdatum oder gleiche E-Mail-Adresse).');
        }
    }
}
