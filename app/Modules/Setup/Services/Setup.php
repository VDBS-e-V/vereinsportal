<?php

namespace App\Modules\Setup\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\People\Models\Person;
use App\Modules\Sso\Services\OAuthKeys;
use App\Modules\System\Support\Redactor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

/** Die Schritte der Einrichtung im Browser: Voraussetzungen, Schlüssel, Datenbank, erste Administration, Abschluss. */
class Setup
{
    public function lockPath(): string
    {
        return (string) config('portal.setup.lock', storage_path('app/setup.lock'));
    }

    /** Die Einrichtung ist offen, solange ein Token gesetzt ist und noch keine Sperrdatei existiert. */
    public function isOpen(): bool
    {
        return strlen((string) config('portal.setup.token', '')) >= 16 && ! is_file($this->lockPath());
    }

    public function tokenValid(string $token): bool
    {
        $configured = (string) config('portal.setup.token', '');

        return $configured !== '' && hash_equals($configured, $token);
    }

    /** @return list<array{label: string, status: string, detail: string}> status: ok, warning, error */
    public function requirements(bool $secure): array
    {
        $rows = [];
        $add = function (string $label, string $status, string $detail) use (&$rows): void {
            $rows[] = ['label' => $label, 'status' => $status, 'detail' => $detail];
        };

        $add('PHP-Version', version_compare(PHP_VERSION, '8.4.1', '>=') ? 'ok' : 'error', PHP_VERSION.' (nötig: 8.4.1 oder höher)');

        foreach (['mbstring', 'openssl', 'pdo_mysql', 'fileinfo', 'ctype', 'json', 'tokenizer', 'xml', 'dom', 'curl'] as $extension) {
            $add('Erweiterung '.$extension, extension_loaded($extension) ? 'ok' : 'error', extension_loaded($extension) ? 'vorhanden' : 'fehlt');
        }

        foreach (['zip', 'gd', 'intl'] as $extension) {
            $add('Erweiterung '.$extension, extension_loaded($extension) ? 'ok' : 'warning', extension_loaded($extension) ? 'vorhanden' : 'fehlt (einzelne Funktionen fehlen dann)');
        }

        foreach (['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $path) {
            $add('Schreibrechte '.$path, is_writable(base_path($path)) ? 'ok' : 'error', is_writable(base_path($path)) ? 'beschreibbar' : 'nicht beschreibbar (Rechte im Kundenmenü oder per FTP auf 755/775 stellen)');
        }

        $add('Anwendungsschlüssel (APP_KEY)', filled(config('app.key')) ? 'ok' : 'warning', filled(config('app.key')) ? 'gesetzt' : 'fehlt: Schritt „Schlüssel“ erzeugt einen');
        $add('Adresse (APP_URL)', str_starts_with((string) config('app.url'), 'https://') ? 'ok' : 'warning', (string) config('app.url').(str_starts_with((string) config('app.url'), 'https://') ? '' : ' (im Livebetrieb https:// verwenden)'));
        $add('HTTPS', $secure ? 'ok' : 'warning', $secure ? 'aktiv' : 'Diese Seite wurde nicht über HTTPS aufgerufen. Passwörter nur über HTTPS eingeben.');
        $add('Debug-Modus (APP_DEBUG)', config('app.debug') ? 'warning' : 'ok', config('app.debug') ? 'an: im Livebetrieb APP_DEBUG=false setzen' : 'aus');
        $add('Cron-Schlüssel (CRON_KEY)', strlen((string) config('portal.cron.key', '')) >= 32 ? 'ok' : 'warning', strlen((string) config('portal.cron.key', '')) >= 32 ? 'gesetzt' : 'fehlt oder zu kurz (mindestens 32 Zeichen), ohne ihn laufen Planer und Warteschlange nicht');

        try {
            DB::connection()->getPdo();
            $add('Datenbank', 'ok', 'Verbindung steht ('.DB::connection()->getDriverName().')');
        } catch (Throwable $exception) {
            $add('Datenbank', 'error', 'Keine Verbindung: '.Redactor::clean($exception->getMessage(), 160));
        }

        return $rows;
    }

    public function hasErrors(array $rows): bool
    {
        return in_array('error', array_column($rows, 'status'), true);
    }

    /** Erzeugt einen Anwendungsschlüssel zum Eintragen in die .env (wird nicht automatisch geschrieben). */
    public function newKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /** @return array{ok: bool, output: string} */
    public function migrate(): array
    {
        try {
            $exit = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            $seed = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder', '--force' => true]);
            $output .= Artisan::output();

            return ['ok' => $exit === 0 && $seed === 0, 'output' => Redactor::clean($output, 4000)];
        } catch (Throwable $exception) {
            return ['ok' => false, 'output' => Redactor::clean($exception->getMessage(), 800)];
        }
    }

    public function administrationExists(): bool
    {
        $role = Role::query()->where('key', RoleKey::Administration->value)->first();

        return $role !== null && $role->assignments()->whereNull('ends_at')->exists();
    }

    /** @param  array{first_name: string, last_name: string, email: string, birth_date: string, password: string}  $data */
    public function createAdministration(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $person = Person::query()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'birth_date' => $data['birth_date'],
                'email' => mb_strtolower(trim($data['email'])),
                'country_code' => 'DE',
            ]);

            $user = User::query()->create([
                'person_id' => $person->id,
                'email' => $person->email,
                'password' => Hash::make($data['password']),
                'status' => UserStatus::Active->value,
                'session_version' => 1,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $role = Role::query()->where('key', RoleKey::Administration->value)->firstOrFail();
            $user->roleAssignments()->create(['role_id' => $role->id, 'source' => RoleAssignmentSource::Console, 'starts_at' => now(), 'comment' => 'Bei der Einrichtung im Browser angelegt.']);

            AuditLog::record(AuditEvent::RoleAssigned, null, $user, ['rolle' => $role->name, 'kommentar' => 'Einrichtung im Browser']);

            return $user;
        });
    }

    /** Erzeugt die Anbindungsschlüssel (Passport), falls sie fehlen. @return array{ok: bool, message: string} */
    public function passportKeys(): array
    {
        if (is_file(storage_path('oauth-private.key')) && is_file(storage_path('oauth-public.key'))) {
            return ['ok' => true, 'message' => 'Die Schlüssel für Anbindungen (SSO) waren schon vorhanden.'];
        }

        try {
            app(OAuthKeys::class)->ensure();

            return ['ok' => true, 'message' => 'Die Schlüssel für Anbindungen (SSO) wurden erzeugt.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'Die Schlüssel konnten nicht erzeugt werden: '.Redactor::clean($exception->getMessage(), 160)];
        }
    }

    public function lock(): bool
    {
        return @file_put_contents($this->lockPath(), 'Eingerichtet am '.now()->toDateTimeString()."\n") !== false;
    }
}
