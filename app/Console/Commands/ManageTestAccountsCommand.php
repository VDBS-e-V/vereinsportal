<?php

namespace App\Console\Commands;

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\TwoFactorMethodType;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\TwoFactorMethod;
use App\Modules\Identity\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ManageTestAccountsCommand extends Command
{
    private const CREDENTIALS_PATH = 'test-accounts.json';

    protected $signature = 'vdbs:test-accounts
        {action : create oder delete}';

    protected $description =
        'Erstellt oder löscht lokale Testkonten für alle Portalrollen.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->components->error(
                'Testkonten dürfen ausschließlich in local/testing verwaltet werden.',
            );

            return self::FAILURE;
        }

        return match (mb_strtolower((string) $this->argument('action'))) {
            'create' => $this->createAccounts(),
            'delete' => $this->deleteAccounts(),
            default => $this->invalidAction(),
        };
    }

    private function createAccounts(): int
    {
        $roles = Role::query()
            ->whereIn(
                'key',
                array_map(
                    static fn (RoleKey $role): string => $role->value,
                    RoleKey::cases(),
                ),
            )
            ->get()
            ->keyBy('key');

        if ($roles->count() !== count(RoleKey::cases())) {
            $this->components->error(
                'Nicht alle Systemrollen sind vorhanden. Bitte zuerst die Rollen seeden.',
            );

            return self::FAILURE;
        }

        $protectedAdminEmail = $this->protectedAdminEmail();

        foreach (RoleKey::cases() as $roleKey) {
            $email = $this->emailFor($roleKey);

            if ($protectedAdminEmail !== '' && $email === $protectedAdminEmail) {
                $this->components->error(
                    'Die Testkonto-Adresse kollidiert mit VDB_DEV_ADMIN_EMAIL.',
                );

                return self::FAILURE;
            }

            $existingUser = User::query()
                ->where('email', $email)
                ->first();

            if (
                $existingUser !== null
                && ! $this->isManagedTestUser($existingUser)
            ) {
                $this->components->error(
                    sprintf(
                        'Die Adresse %s gehört bereits zu einem nicht verwalteten Konto.',
                        $email,
                    ),
                );

                return self::FAILURE;
            }

            if ($existingUser === null) {
                $existingPerson = Person::query()
                    ->where('email', $email)
                    ->first();

                if (
                    $existingPerson !== null
                    && ! $this->isFixturePerson($existingPerson, $roleKey)
                ) {
                    $this->components->error(
                        sprintf(
                            'Die Adresse %s gehört bereits zu einer regulären Person.',
                            $email,
                        ),
                    );

                    return self::FAILURE;
                }
            }
        }

        /** @var list<array{role: string, role_name: string, email: string, password: string, totp_secret: string}> $credentials */
        $credentials = [];

        DB::transaction(function () use ($roles, &$credentials): void {
            foreach (RoleKey::cases() as $roleKey) {
                $email = $this->emailFor($roleKey);
                $password = Str::password(24);
                $totpSecret = $this->generateTotpSecret();

                $user = User::query()
                    ->where('email', $email)
                    ->first();

                $wasExisting = $user !== null;

                $person = $user?->person;

                if ($person === null) {
                    $person = Person::query()
                        ->where('email', $email)
                        ->first();
                }

                if ($person === null) {
                    $person = new Person;
                }

                $role = $roles->get($roleKey->value);

                if (! $role instanceof Role) {
                    throw new RuntimeException(
                        'Systemrolle konnte nicht geladen werden.',
                    );
                }

                $person->first_name = 'Testkonto';
                $person->last_name = $role->name;
                $person->birth_date = Carbon::parse('2000-01-01');
                $person->email = $email;
                $person->country_code = 'DE';
                $person->save();

                if ($user === null) {
                    $user = new User;
                    $user->session_version = 1;
                } else {
                    $user->session_version = max(
                        1,
                        $user->session_version + 1,
                    );
                }

                $user->person_id = $person->id;
                $user->email = $email;
                $user->password = $password;
                $user->status = UserStatus::Active;
                $user->email_verified_at = now();
                $user->force_password_change_at = null;
                $user->last_login_at = null;
                $user->remember_token = null;
                $user->anonymized_at = null;
                $user->anonymized_ref = null;
                $user->save();

                if ($wasExisting) {
                    $this->clearTransientAccountData($user);
                }

                RoleAssignment::query()
                    ->where('user_id', $user->id)
                    ->delete();

                RoleAssignment::query()->create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'source' => RoleAssignmentSource::Console,
                    'source_type' => self::class,
                    'source_id' => null,
                    'starts_at' => now(),
                    'ends_at' => null,
                    'granted_by_user_id' => null,
                    'comment' => 'Lokales Testkonto für Rollenprüfungen.',
                ]);

                TwoFactorMethod::query()
                    ->where('user_id', $user->id)
                    ->delete();

                TwoFactorMethod::query()->create([
                    'user_id' => $user->id,
                    'type' => TwoFactorMethodType::Totp,
                    'secret' => $totpSecret,
                    'confirmed_at' => now(),
                    'disabled_at' => null,
                ]);

                $credentials[] = [
                    'role' => $roleKey->value,
                    'role_name' => $role->name,
                    'email' => $email,
                    'password' => $password,
                    'totp_secret' => $totpSecret,
                ];
            }
        });

        $payload = json_encode(
            [
                'generated_at' => now()->toIso8601String(),
                'warning' => 'Nur für lokale Tests. Nicht committen oder weitergeben.',
                'accounts' => $credentials,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if (! is_string($payload)) {
            throw new RuntimeException(
                'Zugangsdaten konnten nicht serialisiert werden.',
            );
        }

        if (! Storage::disk('local')->put(self::CREDENTIALS_PATH, $payload)) {
            throw new RuntimeException(
                'Zugangsdaten-Datei konnte nicht geschrieben werden.',
            );
        }

        $this->components->info(
            sprintf(
                '%d Testkonten wurden erstellt/aktualisiert.',
                count($credentials),
            ),
        );
        $this->line(
            'Zugangsdaten: '.Storage::disk('local')->path(self::CREDENTIALS_PATH),
        );
        $this->components->warn(
            'Die Datei liegt im privaten, gitignored Storage und enthält Test-Passwörter sowie TOTP-Secrets.',
        );

        return self::SUCCESS;
    }

    private function deleteAccounts(): int
    {
        $protectedAdminEmail = $this->protectedAdminEmail();

        $userIds = RoleAssignment::query()
            ->where('source', RoleAssignmentSource::Console)
            ->where('source_type', self::class)
            ->pluck('user_id')
            ->unique()
            ->values();

        $deleted = 0;

        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);

            if (! $user instanceof User) {
                continue;
            }

            if (
                $protectedAdminEmail !== ''
                && mb_strtolower($user->email) === $protectedAdminEmail
            ) {
                $this->components->warn(
                    'Das konfigurierte Default-Admin-Konto wurde bewusst übersprungen.',
                );

                continue;
            }

            try {
                DB::transaction(function () use ($user): void {
                    $personId = $user->person_id;

                    $this->clearTransientAccountData($user);

                    DB::table('privacy_consents')
                        ->where('user_id', $user->id)
                        ->update(['user_id' => null]);

                    DB::table('email_deliveries')
                        ->where('sender_user_id', $user->id)
                        ->update(['sender_user_id' => null]);

                    DB::table('email_templates')
                        ->where('updated_by_user_id', $user->id)
                        ->update(['updated_by_user_id' => null]);

                    DB::table('role_assignments')
                        ->where('granted_by_user_id', $user->id)
                        ->update(['granted_by_user_id' => null]);

                    RoleAssignment::query()
                        ->where('user_id', $user->id)
                        ->delete();

                    TwoFactorMethod::query()
                        ->where('user_id', $user->id)
                        ->delete();

                    $user->delete();

                    if ($personId !== null) {
                        $person = Person::query()->find($personId);

                        if (
                            $person instanceof Person
                            && ! $person->memberships()->exists()
                            && ! $person->portalInvitations()->exists()
                        ) {
                            $person->delete();
                        }
                    }
                });
            } catch (QueryException $exception) {
                $this->components->error(
                    sprintf(
                        'Testkonto %s ist noch mit dauerhaften Testdaten verknüpft und konnte nicht sicher gelöscht werden.',
                        $user->email,
                    ),
                );
                $this->line(
                    'Entfernen Sie die abhängigen lokalen Testdaten oder setzen Sie die lokale Datenbank zurück.',
                );

                report($exception);

                return self::FAILURE;
            }

            $deleted++;
        }

        Storage::disk('local')->delete(self::CREDENTIALS_PATH);

        $this->components->info(
            sprintf('%d verwaltete Testkonten wurden gelöscht.', $deleted),
        );

        return self::SUCCESS;
    }

    private function clearTransientAccountData(User $user): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        DB::table('email_change_requests')
            ->where('user_id', $user->id)
            ->delete();

        DB::table('two_factor_email_challenges')
            ->where('user_id', $user->id)
            ->delete();

        DB::table('two_factor_recovery_codes')
            ->where('user_id', $user->id)
            ->delete();
    }

    private function isManagedTestUser(User $user): bool
    {
        return RoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('source', RoleAssignmentSource::Console)
            ->where('source_type', self::class)
            ->exists();
    }

    private function isFixturePerson(Person $person, RoleKey $roleKey): bool
    {
        $role = Role::query()
            ->where('key', $roleKey->value)
            ->first();

        return $role instanceof Role
            && $person->first_name === 'Testkonto'
            && $person->last_name === $role->name;
    }

    private function emailFor(RoleKey $roleKey): string
    {
        return sprintf(
            'test.%s@vdbs.test',
            str_replace('_', '-', $roleKey->value),
        );
    }

    private function protectedAdminEmail(): string
    {
        return mb_strtolower(
            trim((string) config('development.admin.email')),
        );
    }

    private function generateTotpSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($index = 0; $index < 32; $index++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    private function invalidAction(): int
    {
        $this->components->error(
            'Unbekannte Aktion. Verwenden Sie create oder delete.',
        );

        return self::INVALID;
    }
}
