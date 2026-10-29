<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Events\AccountAnonymizing;
use App\Modules\Access\Mail\AccountDeletedMail;
use App\Modules\Access\Mail\AccountDeletionRequestedMail;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Audit\Support\AuditLog;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Konto löschen (Offboarding). Die Löschung ist eine Anonymisierung, keine harte Löschung:
 *  1. Der Nutzer beantragt sie (Passwort, Bestätigungstext). Das Konto ist sofort gesperrt (Status „Löschung angefragt“), alle
 *     Sitzungen enden; eine E-Mail mit Abbruch-Link geht an die Adresse.
 *  2. Während der Wartefrist (config portal.offboarding.grace_days, Standard 14 Tage) kann der Nutzer die Löschung über den
 *     Link abbrechen, die Verwaltung ebenfalls.
 *  3. Danach anonymisiert `account:purge`: Rollen enden, Zwei-Faktor und Zugangsdaten werden entfernt, Person und Konto verlieren
 *     alle personenbezogenen Angaben, andere Module räumen auf (Ereignis AccountAnonymizing), Namen und Adressen im Protokoll werden
 *     unkenntlich gemacht. Datensätze bleiben als „Gelöschte Person“ bestehen, damit Verweise gültig bleiben.
 * Die letzte aktive Administration kann ihr Konto nicht löschen.
 */
final class AccountDeletion
{
    public const DELETED_NAME_FIRST = 'Gelöschte';

    public const DELETED_NAME_LAST = 'Person';

    public static function graceDays(): int
    {
        return max(1, (int) config('portal.offboarding.grace_days', 14));
    }

    /** Gründe, warum das Konto nicht gelöscht werden kann (leer = möglich). @return list<string> */
    public function blockers(User $user): array
    {
        $blockers = [];

        $isAdministrator = $user->activeRoleAssignments()->whereHas('role', fn ($role) => $role->where('key', RoleKey::Administration->value))->exists();

        $otherAdministrators = User::query()
            ->where('status', UserStatus::Active->value)
            ->where('id', '!=', $user->id)
            ->whereHas('activeRoleAssignments', fn ($assignments) => $assignments->whereHas('role', fn ($role) => $role->where('key', RoleKey::Administration->value)))
            ->count();

        if ($isAdministrator && $otherAdministrators === 0) {
            $blockers[] = 'Sie sind die letzte aktive Administration. Vergeben Sie die Rolle zuerst an eine andere Person.';
        }

        return $blockers;
    }

    public function request(User $user): void
    {
        if ($user->status !== UserStatus::Active) {
            throw new DomainException('Nur aktive Konten können die Löschung beantragen.');
        }

        if ($blockers = $this->blockers($user)) {
            throw new DomainException($blockers[0]);
        }

        $user->forceFill([
            'status' => UserStatus::PendingDeletion,
            'deletion_requested_at' => now(),
            'session_version' => $user->session_version + 1,
        ])->save();

        AuditLog::record(AuditEvent::AccountDeletionRequested, $user, $user);

        $url = URL::temporarySignedRoute('account.deletion.cancel', now()->addDays(self::graceDays()), ['user' => $user->id]);

        Mail::to($user->email)->send(new AccountDeletionRequestedMail($url, self::graceDays(), now()->addDays(self::graceDays())->format('d.m.Y')));
    }

    /** Bricht die beantragte Löschung ab (vom Nutzer über den Link oder durch die Verwaltung). */
    public function cancel(User $user, ?User $by = null): void
    {
        if ($user->status !== UserStatus::PendingDeletion) {
            throw new DomainException('Für dieses Konto ist keine Löschung beantragt.');
        }

        $user->forceFill(['status' => UserStatus::Active, 'deletion_requested_at' => null])->save();

        AuditLog::record(AuditEvent::AccountDeletionCancelled, $by ?? $user, $user);
    }

    /** Anonymisiert alle Konten, deren Wartefrist abgelaufen ist. @return array{done: int, skipped: int} */
    public function purgeDue(): array
    {
        $done = 0;
        $skipped = 0;

        User::query()
            ->where('status', UserStatus::PendingDeletion->value)
            ->where('deletion_requested_at', '<=', now()->subDays(self::graceDays()))
            ->each(function (User $user) use (&$done, &$skipped): void {
                try {
                    $this->anonymize($user);
                    $done++;
                } catch (DomainException $exception) {
                    $skipped++;
                    report($exception);
                }
            });

        return ['done' => $done, 'skipped' => $skipped];
    }

    public function anonymize(User $user): void
    {
        if ($user->status === UserStatus::Anonymized) {
            return;
        }

        if ($blockers = $this->blockers($user)) {
            throw new DomainException($blockers[0]);
        }

        $oldEmail = $user->email;
        $person = $user->person;
        $personId = $person?->id;
        $ref = (string) Str::ulid();
        $placeholder = 'geloescht-'.Str::lower($ref).'@invalid.example';

        $this->sendNotice($oldEmail);

        DB::transaction(function () use ($user, $person, $personId, $oldEmail, $ref, $placeholder): void {
            $user->roleAssignments()
                ->where(fn ($open) => $open->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->update(['ends_at' => now()]);

            $user->twoFactorMethods()->delete();
            $user->emailChangeRequests()->delete();
            DB::table('two_factor_recovery_codes')->where('user_id', $user->id)->delete();
            DB::table('two_factor_email_challenges')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if (filled($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            Event::dispatch(new AccountAnonymizing($user, $person));

            $person?->forceFill([
                'title' => null,
                'first_name' => self::DELETED_NAME_FIRST,
                'name_addition' => null,
                'last_name' => self::DELETED_NAME_LAST,
                'birth_date' => '1900-01-01',
                'email' => $placeholder,
                'phone' => null,
                'street' => null,
                'house_number' => null,
                'postal_code' => null,
                'city' => null,
                'country_code' => 'DE',
            ])->save();

            $user->forceFill([
                'email' => $placeholder,
                'password' => Str::random(64),
                'status' => UserStatus::Anonymized,
                'remember_token' => null,
                'preferred_two_factor_method' => null,
                'avatar_path' => null,
                'session_version' => $user->session_version + 1,
                'anonymized_at' => now(),
                'anonymized_ref' => $ref,
            ])->save();

            $this->scrubAuditTrail($user->id, $personId);
        });

        AuditLog::record(AuditEvent::AccountAnonymized, null, $user, [], 'Gelöschtes Konto');
    }

    /** Macht Namen und Adressen im Protokoll unkenntlich (die Einträge selbst bleiben als Nachweis bestehen). */
    private function scrubAuditTrail(int $userId, ?int $personId): void
    {
        AuditEntry::query()->where('actor_user_id', $userId)->toBase()->update(['actor_label' => 'Gelöschtes Konto']);

        AuditEntry::query()->where('subject_type', 'user')->where('subject_id', $userId)->toBase()->update(['subject_label' => 'Gelöschtes Konto', 'context' => null]);

        if ($personId !== null) {
            AuditEntry::query()->where('subject_type', 'person')->where('subject_id', $personId)->toBase()->update(['subject_label' => 'Gelöschte Person', 'context' => null]);
        }
    }

    private function sendNotice(string $email): void
    {
        try {
            Mail::to($email)->send(new AccountDeletedMail);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
