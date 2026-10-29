<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Mail\PasswordResetMail;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\RecoveryCodes;
use App\Modules\Access\Services\Totp;
use App\Modules\AccessGovernance\Enums\Capability;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Support\Capabilities;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function uaGrant(User $user, RoleKey $key, array $attributes = []): RoleAssignment
{
    return RoleAssignment::query()->create($attributes + [
        'user_id' => $user->id,
        'role_id' => Role::query()->where('key', $key->value)->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);
}

function uaUser(?RoleKey $role = null, array $attributes = [], array $person = []): User
{
    $personModel = Person::factory()->create($person);
    $user = User::factory()->create($attributes + ['person_id' => $personModel->id, 'email' => $personModel->email]);

    if ($role !== null) {
        uaGrant($user, $role);
    }

    return $user;
}

// ---------- Berechtigungen ----------

it('sends guests to the login', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->get(route('admin.users.create'))->assertRedirect(route('login'));
});

it('forbids accounts without a suitable role', function () {
    $member = uaUser(RoleKey::Member);
    $target = uaUser();

    $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
    $this->get(route('admin.users.show', $target))->assertForbidden();
    $this->get(route('admin.users.create'))->assertForbidden();
    $this->post(route('admin.users.disable', $target))->assertForbidden();
});

it('lets the board view but not change users', function () {
    $board = uaUser(RoleKey::BoardMember);
    $target = uaUser();

    $this->actingAs($board)->get(route('admin.users.index'))->assertOk();
    $this->get(route('admin.users.show', $target))->assertOk();
    $this->get(route('admin.users.create'))->assertForbidden();
    $this->post(route('admin.users.disable', $target))->assertForbidden();
    $this->post(route('admin.users.roles.store', $target), ['role_id' => Role::query()->where('key', 'member')->value('id')])->assertForbidden();
});

it('derives capabilities from currently valid roles only', function () {
    $user = uaUser();
    uaGrant($user, RoleKey::AdministrationStaff, ['ends_at' => now()->subHour()]);

    expect(Capabilities::has($user, Capability::UsersView))->toBeFalse();

    uaGrant($user, RoleKey::BoardMember, ['starts_at' => now()->addDay()]);
    expect(Capabilities::has($user, Capability::UsersView))->toBeFalse();

    uaGrant($user, RoleKey::BoardMember);
    expect(Capabilities::has($user, Capability::UsersView))->toBeTrue()
        ->and(Capabilities::has($user, Capability::UsersManage))->toBeFalse();
});

it('only lets the administration assign the administration role', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $admin = uaUser(RoleKey::Administration);

    expect(Capabilities::canAssign($staff, RoleKey::Member))->toBeTrue()
        ->and(Capabilities::canAssign($staff, RoleKey::Administration))->toBeFalse()
        ->and(Capabilities::canAssign($admin, RoleKey::Administration))->toBeTrue()
        ->and(Capabilities::canAssign(uaUser(RoleKey::BoardMember), RoleKey::Member))->toBeFalse();
});

it('shows the administration link in the header only to permitted users', function () {
    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->get(route('home'))->assertSee(route('admin.users.index'), false);
    $this->actingAs(uaUser(RoleKey::Member))->get(route('home'))->assertDontSee(route('admin.users.index'), false);
});

// ---------- Liste ----------

it('lists users with name, email, status and roles', function () {
    $staff = uaUser(RoleKey::AdministrationStaff, [], ['first_name' => 'Sabine', 'last_name' => 'Schmidt']);
    uaUser(RoleKey::Team, ['status' => UserStatus::Disabled], ['first_name' => 'Tom', 'last_name' => 'Team', 'email' => 'tom@example.org']);

    $this->actingAs($staff)->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Sabine Schmidt')
        ->assertSee('Tom Team')
        ->assertSee('tom@example.org')
        ->assertSee('Gesperrt')
        ->assertSee('Teamende')
        ->assertSee('2 Nutzer');
});

it('searches by name and email, filters by status and role and shows removable filter tags', function () {
    $staff = uaUser(RoleKey::AdministrationStaff, [], ['first_name' => 'Sabine', 'last_name' => 'Schmidt']);
    uaUser(RoleKey::Team, [], ['first_name' => 'Tom', 'last_name' => 'Tester', 'email' => 'tom@example.org']);
    uaUser(null, ['status' => UserStatus::Disabled], ['first_name' => 'Gerda', 'last_name' => 'Gesperrt', 'email' => 'gerda@example.org']);
    $this->actingAs($staff);

    $this->get(route('admin.users.index', ['q' => 'tester']))->assertSee('Tom Tester')->assertDontSee('Gerda Gesperrt')->assertSee('Suche: tester')->assertSee('Filter entfernen: Suche: tester');
    $this->get(route('admin.users.index', ['q' => 'gerda@']))->assertSee('Gerda Gesperrt')->assertDontSee('Tom Tester');
    $this->get(route('admin.users.index', ['status' => 'disabled']))->assertSee('Gerda Gesperrt')->assertDontSee('Tom Tester')->assertSee('Status: Gesperrt');
    $this->get(route('admin.users.index', ['role' => 'team']))->assertSee('Tom Tester')->assertDontSee('Gerda Gesperrt')->assertSee('Rolle: Teamende');
});

it('escapes wildcard characters in the search', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    uaUser(null, [], ['first_name' => 'Anna', 'last_name' => 'Arnold', 'email' => 'anna@example.org']);

    $this->actingAs($staff)->get(route('admin.users.index', ['q' => '%']))->assertSee('Keine Nutzer gefunden')->assertDontSee('Anna Arnold');
});

it('sorts by name and email in both directions', function () {
    $staff = uaUser(RoleKey::AdministrationStaff, [], ['first_name' => 'Sabine', 'last_name' => 'Schmidt']);
    uaUser(null, [], ['first_name' => 'Anna', 'last_name' => 'Adler', 'email' => 'zzz@example.org']);
    uaUser(null, [], ['first_name' => 'Zora', 'last_name' => 'Zander', 'email' => 'aaa@example.org']);
    $this->actingAs($staff);

    $this->get(route('admin.users.index', ['sort' => 'name', 'direction' => 'asc']))->assertSeeInOrder(['Anna Adler', 'Sabine Schmidt', 'Zora Zander']);
    $this->get(route('admin.users.index', ['sort' => 'name', 'direction' => 'desc']))->assertSeeInOrder(['Zora Zander', 'Sabine Schmidt', 'Anna Adler']);
    $this->get(route('admin.users.index', ['sort' => 'email', 'direction' => 'asc']))->assertSeeInOrder(['aaa@example.org', 'zzz@example.org']);
    $this->get(route('admin.users.index', ['sort' => 'unbekannt', 'direction' => 'sideways']))->assertOk();
});

it('paginates and keeps the filters in the page links', function () {
    $staff = uaUser(RoleKey::AdministrationStaff, [], ['last_name' => 'Aaa']);
    foreach (range(1, 19) as $i) {
        uaUser(null, [], ['last_name' => sprintf('Nachname%02d', $i)]);
    }
    $this->actingAs($staff);

    $first = $this->get(route('admin.users.index', ['sort' => 'name']))->assertOk()->assertSee('Seite 1 von 2')->assertSee('20 Nutzer');
    $first->assertSee('sort=name', false)->assertSee('page=2', false);
    $this->get(route('admin.users.index', ['sort' => 'name', 'page' => 2]))->assertSee('Seite 2 von 2')->assertSee('Nachname15')->assertDontSee('Nachname01');
});

it('shows the counts of the overview tiles', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $protected = uaUser();
    $protected->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret(), 'confirmed_at' => now()]);
    uaUser(null, ['status' => UserStatus::Disabled]);
    uaUser(null, ['status' => UserStatus::PendingVerification]);

    $response = $this->actingAs($staff)->get(route('admin.users.index'))->assertOk();
    $stats = $response->viewData('stats');

    expect($stats)->toBe(['active' => 2, 'invited' => 1, 'disabled' => 1, 'withoutTwoFactor' => 1]);
});

// ---------- Details ----------

it('shows account, person, two-factor state and roles on the detail page', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser(RoleKey::Team, [], ['first_name' => 'Tom', 'last_name' => 'Team', 'birth_date' => '1990-05-17', 'phone' => '030 1234']);
    uaGrant($target, RoleKey::Member, ['ends_at' => now()->subDay(), 'starts_at' => now()->subYear()]);

    $this->actingAs($staff)->get(route('admin.users.show', $target))
        ->assertOk()
        ->assertSee('Tom Team')
        ->assertSee('17.05.1990')
        ->assertSee('030 1234')
        ->assertSee('Teamende')
        ->assertSee('Frühere Rollen (1)')
        ->assertSee('Nicht eingerichtet');
});

it('does not offer to disable your own account', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('admin.users.show', $staff))->assertOk()->assertDontSee('data-vdbs-dialog-open="disable-user"', false);
});

// ---------- Anlegen und Einladen ----------

it('creates person and account and sends the invitation', function () {
    Mail::fake();
    $staff = uaUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('admin.users.store'), [
        'first_name' => 'Nora', 'last_name' => 'Neu', 'birth_date' => '1992-02-02', 'email' => 'Nora@Example.org', 'role' => 'member',
    ])->assertRedirect()->assertSessionHas('success');

    $user = User::query()->where('email', 'nora@example.org')->firstOrFail();

    expect($user->status)->toBe(UserStatus::PendingVerification)
        ->and($user->person->fullName())->toBe('Nora Neu')
        ->and($user->person->email)->toBe('nora@example.org')
        ->and($user->activeRoleAssignments()->count())->toBe(1);

    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('nora@example.org') && str_contains($mail->url, 'einladung=1'));
});

it('validates the new account', function () {
    $staff = uaUser(RoleKey::AdministrationStaff, [], ['email' => 'vorhanden@example.org']);
    $this->actingAs($staff);

    $this->post(route('admin.users.store'), [])->assertSessionHasErrors(['first_name', 'last_name', 'birth_date', 'email']);
    $this->post(route('admin.users.store'), ['first_name' => 'A', 'last_name' => 'B', 'birth_date' => '2999-01-01', 'email' => 'x@example.org'])->assertSessionHasErrors('birth_date');
    $this->post(route('admin.users.store'), ['first_name' => 'A', 'last_name' => 'B', 'birth_date' => '1990-01-01', 'email' => 'vorhanden@example.org'])->assertSessionHasErrors('email');
    $this->post(route('admin.users.store'), ['first_name' => 'A', 'last_name' => 'B', 'birth_date' => '1990-01-01', 'email' => 'x@example.org', 'role' => 'erfunden'])->assertSessionHasErrors('role');
});

it('does not let the staff create administrators but lets the administration do it', function () {
    Mail::fake();
    $payload = ['first_name' => 'A', 'last_name' => 'B', 'birth_date' => '1990-01-01', 'email' => 'neu-admin@example.org', 'role' => 'administration'];

    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->post(route('admin.users.store'), $payload)->assertForbidden();
    expect(User::query()->where('email', 'neu-admin@example.org')->exists())->toBeFalse();

    $this->actingAs(uaUser(RoleKey::Administration))->post(route('admin.users.store'), $payload)->assertRedirect();
    expect(User::query()->where('email', 'neu-admin@example.org')->exists())->toBeTrue();
});

it('activates the account when the invitation is accepted and lets the user log in', function () {
    Mail::fake();
    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->post(route('admin.users.store'), ['first_name' => 'Nora', 'last_name' => 'Neu', 'birth_date' => '1992-02-02', 'email' => 'nora@example.org']);
    auth()->logout();

    $url = Mail::sent(InvitationMail::class)->first()->url;
    preg_match('~/passwort-zuruecksetzen/([^?]+)\?email=([^&]+)~', $url, $parts);

    $this->get($url)->assertOk()->assertSee('Willkommen im Portal');

    $this->post(route('password.update'), ['token' => $parts[1], 'email' => urldecode($parts[2]), 'password' => 'Willkommen-2026-X', 'password_confirmation' => 'Willkommen-2026-X'])
        ->assertRedirect(route('login'));

    $user = User::query()->where('email', 'nora@example.org')->firstOrFail();
    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Willkommen-2026-X', $user->password))->toBeTrue();

    $this->post('/login', ['email' => 'nora@example.org', 'password' => 'Willkommen-2026-X'])->assertRedirect(route('home'));
});

it('keeps the invitation valid for seven days but not longer', function () {
    Mail::fake();
    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->post(route('admin.users.store'), ['first_name' => 'Nora', 'last_name' => 'Neu', 'birth_date' => '1992-02-02', 'email' => 'nora@example.org']);
    auth()->logout();

    $url = Mail::sent(InvitationMail::class)->first()->url;
    preg_match('~/passwort-zuruecksetzen/([^?]+)\?email=([^&]+)~', $url, $parts);
    $body = ['token' => $parts[1], 'email' => urldecode($parts[2]), 'password' => 'Willkommen-2026-X', 'password_confirmation' => 'Willkommen-2026-X'];

    $this->travel(8)->days();
    $this->post(route('password.update'), $body)->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'nora@example.org')->firstOrFail()->status)->toBe(UserStatus::PendingVerification);
});

it('resends the invitation only to invited accounts', function () {
    Mail::fake();
    $staff = uaUser(RoleKey::AdministrationStaff);
    $invited = uaUser(null, ['status' => UserStatus::PendingVerification]);
    $active = uaUser();
    $this->actingAs($staff);

    $this->post(route('admin.users.invite', $invited))->assertSessionHas('success');
    Mail::assertSent(InvitationMail::class, 1);

    $this->post(route('admin.users.invite', $active))->assertSessionHas('warning');
    Mail::assertSent(InvitationMail::class, 1);
});

// ---------- Sperren ----------

it('disables and re-enables an account and ends its sessions', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser(RoleKey::Member);
    $this->actingAs($staff);

    $this->post(route('admin.users.disable', $target))->assertRedirect(route('admin.users.show', $target))->assertSessionHas('success');

    expect($target->fresh()->status)->toBe(UserStatus::Disabled)
        ->and($target->fresh()->session_version)->toBe(2);

    $this->post('/logout');
    $this->post('/login', ['email' => $target->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->actingAs($staff)->post(route('admin.users.enable', $target))->assertSessionHas('success');
    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

it('does not let you disable yourself or the last administrator', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $admin = uaUser(RoleKey::Administration);
    $otherAdmin = uaUser(RoleKey::Administration);

    $this->actingAs($staff)->post(route('admin.users.disable', $staff))->assertSessionHas('warning');
    expect($staff->fresh()->status)->toBe(UserStatus::Active);

    $this->post(route('admin.users.disable', $otherAdmin))->assertSessionHas('success');
    $this->post(route('admin.users.disable', $admin))->assertSessionHas('warning');
    expect($admin->fresh()->status)->toBe(UserStatus::Active);
});

it('only enables disabled accounts', function () {
    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->post(route('admin.users.enable', uaUser()))->assertSessionHas('warning');
});

// ---------- Zwei-Faktor und Passwort ----------

it('resets all two-factor methods and recovery codes', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser();
    $target->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret(), 'confirmed_at' => now()]);
    $target->twoFactorMethods()->create(['type' => TwoFactorMethodType::Email, 'confirmed_at' => now()]);
    app(RecoveryCodes::class)->generate($target);

    $this->actingAs($staff)->post(route('admin.users.two-factor.reset', $target))->assertSessionHas('success');

    expect($target->twoFactorMethods()->whereNull('disabled_at')->count())->toBe(0)
        ->and(app(RecoveryCodes::class)->remaining($target))->toBe(0)
        ->and($target->fresh()->preferred_two_factor_method)->toBeNull();
});

it('sends a password reset link to active accounts only', function () {
    Mail::fake();
    $staff = uaUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->post(route('admin.users.password-link', uaUser(null, [], ['email' => 'aktiv@example.org'])))->assertSessionHas('success');
    Mail::assertSent(PasswordResetMail::class, fn (PasswordResetMail $mail): bool => $mail->hasTo('aktiv@example.org'));

    $this->post(route('admin.users.password-link', uaUser(null, ['status' => UserStatus::Disabled])))->assertSessionHas('warning');
    Mail::assertSent(PasswordResetMail::class, 1);
});

// ---------- Rollen ----------

it('assigns a role with an end date and a comment and records who granted it', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser();
    $roleId = Role::query()->where('key', 'team')->value('id');

    $this->actingAs($staff)->post(route('admin.users.roles.store', $target), ['role_id' => $roleId, 'ends_at' => now()->addMonth()->format('Y-m-d'), 'comment' => 'Sommerprojekt'])
        ->assertRedirect(route('admin.users.show', $target))->assertSessionHas('success');

    $assignment = $target->roleAssignments()->first();
    expect($assignment->role_id)->toBe($roleId)
        ->and($assignment->source)->toBe(RoleAssignmentSource::Manual)
        ->and($assignment->granted_by_user_id)->toBe($staff->id)
        ->and($assignment->comment)->toBe('Sommerprojekt')
        ->and($assignment->ends_at->isFuture())->toBeTrue();
});

it('validates the role form and refuses a role the user already has', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser(RoleKey::Team);
    $roleId = Role::query()->where('key', 'team')->value('id');
    $this->actingAs($staff);

    $this->post(route('admin.users.roles.store', $target), [])->assertSessionHasErrors('role_id');
    $this->post(route('admin.users.roles.store', $target), ['role_id' => 9999])->assertSessionHasErrors('role_id');
    $this->post(route('admin.users.roles.store', $target), ['role_id' => $roleId, 'ends_at' => now()->subDay()->format('Y-m-d')])->assertSessionHasErrors('ends_at');
    $this->post(route('admin.users.roles.store', $target), ['role_id' => $roleId])->assertSessionHas('warning');

    expect($target->roleAssignments()->count())->toBe(1);
});

it('does not let the staff assign the administration role', function () {
    $target = uaUser();
    $adminRoleId = Role::query()->where('key', 'administration')->value('id');

    $this->actingAs(uaUser(RoleKey::AdministrationStaff))->post(route('admin.users.roles.store', $target), ['role_id' => $adminRoleId])->assertForbidden();
    expect($target->roleAssignments()->count())->toBe(0);

    $this->actingAs(uaUser(RoleKey::Administration))->post(route('admin.users.roles.store', $target), ['role_id' => $adminRoleId])->assertSessionHas('success');
    expect($target->roleAssignments()->count())->toBe(1);
});

it('ends a role without deleting the assignment', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $target = uaUser(RoleKey::Team);
    $assignment = $target->roleAssignments()->first();

    $this->actingAs($staff)->delete(route('admin.users.roles.destroy', [$target, $assignment]))->assertSessionHas('success');

    expect($assignment->fresh()->ends_at)->not->toBeNull()
        ->and($target->activeRoleAssignments()->count())->toBe(0)
        ->and(RoleAssignment::query()->count())->toBeGreaterThan(0);
});

it('refuses to end an assignment of another user, an administration role by the staff and the last administration', function () {
    $staff = uaUser(RoleKey::AdministrationStaff);
    $admin = uaUser(RoleKey::Administration);
    $other = uaUser(RoleKey::Team);
    $adminAssignment = $admin->roleAssignments()->first();

    $this->actingAs($staff)->delete(route('admin.users.roles.destroy', [$other, $adminAssignment]))->assertNotFound();
    $this->delete(route('admin.users.roles.destroy', [$admin, $adminAssignment]))->assertForbidden();

    $this->actingAs($admin)->delete(route('admin.users.roles.destroy', [$admin, $adminAssignment]))->assertSessionHas('warning');
    expect($adminAssignment->fresh()->ends_at)->toBeNull();
});
