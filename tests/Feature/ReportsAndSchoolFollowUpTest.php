<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function rfUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function rfSchoolAccount(string $name = 'Schule Eins', string $email = 'eins@schule.example'): PartnerUser
{
    $partner = Partner::factory()->create(['name' => $name]);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, rfUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return $account->fresh(['user', 'partner']);
}

function rfValue(array $rows, string $group, string $label): int|string
{
    return collect($rows)->first(fn (array $row): bool => $row['group'] === $group && $row['label'] === $label)['value'];
}

// ---------- Jahresbericht ----------

it('shows the report to the board only, with the year switch and a csv export', function () {
    $this->get(route('board.reports'))->assertRedirect(route('login'));

    foreach ([null, RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::Administration, RoleKey::EducationCoordination] as $role) {
        $this->actingAs(rfUser($role))->get(route('board.reports'))->assertForbidden();
        $this->actingAs(rfUser($role))->get(route('board.reports.export'))->assertForbidden();
    }

    $board = rfUser(RoleKey::BoardMember);
    $year = now()->year;

    $this->actingAs($board)->get(route('board.reports'))->assertOk()->assertSee('Jahresbericht '.$year)->assertSee('Mitglieder am 31.12.')->assertSee('Mitglieder am 31.12.');
    $this->actingAs($board)->get(route('board.reports', ['jahr' => 1999]))->assertOk()->assertSee('Jahresbericht '.$year);

    $body = $this->actingAs($board)->get(route('board.reports.export'))->assertOk()->streamedContent();
    expect($body)->toContain('Jahr;Bereich;Kennzahl;Wert')->toContain('Mitglieder am 31.12.')->toContain((string) $year);
});

it('lists the report in the board navigation', function () {
    $board = rfUser(RoleKey::BoardMember);

    expect(array_column(PortalNavigation::pages(PortalAreas::Vorstand, $board), 'label'))->toContain('Berichte');
});

// ---------- Schulen: Sperren ----------

it('lets the coordination block and unblock a school account', function () {
    $account = rfSchoolAccount();
    $coordination = rfUser(RoleKey::Coordination);
    $pending = rfSchoolAccount('Schule Zwei', 'zwei@schule.example');
    $pending->user->forceFill(['status' => UserStatus::PendingVerification])->save();

    $this->actingAs($coordination)->get(route('partners.show', $account->partner))->assertOk()->assertSee('Sperren');

    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertSessionHas('success');
    expect($account->user->fresh()->status)->toBe(UserStatus::Disabled);

    $this->actingAs($coordination)->get(route('partners.show', $account->partner))->assertSee('Entsperren');
    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertSessionHas('success');
    expect($account->user->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$pending->partner, $pending]))->assertSessionHasErrors('account');
    $this->actingAs($coordination)->post(route('partners.accounts.toggle', [$account->partner, $pending]))->assertNotFound();
    $this->actingAs(rfUser(RoleKey::Member))->post(route('partners.accounts.toggle', [$account->partner, $account]))->assertForbidden();
});
