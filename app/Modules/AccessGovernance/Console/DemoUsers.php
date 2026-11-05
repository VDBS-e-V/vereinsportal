<?php

namespace App\Modules\AccessGovernance\Console;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\People\Models\Person;
use Illuminate\Console\Command;

/**
 * Legt für das lokale Ausprobieren je Rolle ein Testkonto an (nur lokal): <rolle>@demo.test mit gemeinsamem Passwort.
 * Wiederholbar: vorhandene Konten bekommen nur das Passwort neu gesetzt und die Rolle, falls sie fehlt.
 */
class DemoUsers extends Command
{
    protected $signature = 'portal:demo-users {--password=Demo-Passwort-2026 : Passwort für alle Testkonten}';

    protected $description = 'Legt lokal je Rolle ein Testkonto an (<rolle>@demo.test). Nur in der lokalen Umgebung.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->components->error('Dieser Befehl läuft nur in der lokalen Umgebung (APP_ENV=local).');

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
        $password = (string) $this->option('password');
        $rows = [];

        foreach (RoleKey::cases() as $key) {
            if ($key === RoleKey::Guest) {
                continue;
            }

            $email = str_replace('_', '-', $key->value).'@demo.test';
            $person = Person::query()->firstOrCreate(['email' => $email], ['first_name' => 'Demo', 'last_name' => $key->label(), 'birth_date' => '1990-01-01']);
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill(['person_id' => $person->id, 'password' => $password, 'status' => UserStatus::Active, 'email_verified_at' => now()])->save();

            $role = Role::query()->where('key', $key->value)->first();

            if ($role !== null && ! $user->activeRoleAssignments()->where('role_id', $role->id)->exists()) {
                $user->roleAssignments()->create(['role_id' => $role->id, 'source' => RoleAssignmentSource::Console, 'starts_at' => now(), 'comment' => 'Testkonto (portal:demo-users)']);
            }

            $rows[] = [$key->label(), $email];
        }

        $this->table(['Rolle', 'Anmeldung'], $rows);
        $this->line('Passwort für alle: '.$password);
        $this->line('Für Administration, Geschäftsstelle, Vorstand, Geschäftsführung und Kassenführung ist Zwei-Faktor Pflicht. Lokal abschalten: TWO_FACTOR_REQUIRED=false in der .env.');

        return self::SUCCESS;
    }
}
