<?php

namespace Database\Seeders;

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            RoleKey::Guest->value => 'Gast',
            RoleKey::Member->value => 'Vereinsmitglied',
            RoleKey::BoardMember->value => 'Vorstandsmitglied',
            RoleKey::Team->value => 'Teamende',
            RoleKey::AdministrationStaff->value => 'Verwaltung',
            RoleKey::EducationCoordination->value => 'Bildungskoordination',
            RoleKey::Coordination->value => 'Koordination',
            RoleKey::Administration->value => 'Administration',
        ];

        foreach ($roles as $key => $name) {
            Role::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'is_system' => true,
                ],
            );
        }
    }
}
