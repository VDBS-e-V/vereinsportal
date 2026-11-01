<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function epMember(): User
{
    test()->seed(RoleSeeder::class);
    $user = User::factory()->create();
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', RoleKey::Member->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subDay()]);

    return $user;
}

it('shows guests the login on the error pages', function () {
    $this->get('/gibt-es-nicht')->assertNotFound()->assertSee('Seite nicht gefunden')->assertSee('Login');
});

it('shows signed in people their areas instead of the login on the 404 page', function () {
    $this->actingAs(epMember())->get('/gibt-es-nicht')->assertNotFound()->assertSee('Seite nicht gefunden')->assertSee('Mitglieder')->assertDontSee('Zur Anmeldung');
});

it('shows signed in people their areas on the 403 page', function () {
    $this->actingAs(epMember())->get('/admins/system')->assertForbidden()->assertSee('Zugriff nicht erlaubt')->assertSee('Mitglieder');
});
