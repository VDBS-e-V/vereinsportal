<?php

namespace Database\Seeders;

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
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
            RoleKey::AdministrationStaff->value => 'Geschaeftsstelle',
            RoleKey::EducationCoordination->value => 'Bildungsreferenten',
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
