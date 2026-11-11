<?php

namespace App\Modules\Access\Console;

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Console\Command;

/**
 * Gibt einem bestehenden Konto die Rolle Administration (nur über die Konsole). Gedacht für den Start, solange noch niemand die Rolle
 * hat, die sie im Portal vergeben könnte: Die Rolle Administration vergibt sonst nur die Administration selbst.
 */
class GrantAdministration extends Command
{
    protected $signature = 'role:grant-administration {email : E-Mail-Adresse des Kontos}';

    protected $description = 'Gibt einem bestehenden Konto die Rolle Administration (Konsole, mit Protokolleintrag).';

    public function handle(): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->components->error('Es gibt kein Konto mit dieser E-Mail-Adresse.');

            return self::FAILURE;
        }

        $role = Role::query()->where('key', RoleKey::Administration->value)->first();

        if ($role === null) {
            $this->components->error('Die Rolle Administration gibt es noch nicht. Bitte zuerst „php artisan db:seed --class=RoleSeeder“ ausführen.');

            return self::FAILURE;
        }

        if ($user->activeRoleAssignments()->where('role_id', $role->id)->exists()) {
            $this->components->info('Das Konto hat die Rolle Administration bereits.');

            return self::SUCCESS;
        }

        $user->roleAssignments()->create([
            'role_id' => $role->id,
            'source' => RoleAssignmentSource::Console,
            'starts_at' => now(),
            'comment' => 'Per Konsole vergeben (role:grant-administration).',
        ]);

        AuditLog::record(AuditEvent::RoleAssigned, null, $user, ['rolle' => $role->name, 'kommentar' => 'Per Konsole vergeben']);

        $this->components->info('Die Rolle Administration wurde vergeben. Zwei-Faktor ist für diese Rolle Pflicht und wird beim nächsten Aufruf verlangt.');

        return self::SUCCESS;
    }
}
