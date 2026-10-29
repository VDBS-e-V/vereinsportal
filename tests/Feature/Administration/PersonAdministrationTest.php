<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function paUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function paInput(array $overrides = []): array
{
    return $overrides + [
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
        'birth_date' => '1985-04-12',
        'email' => 'Erika@Example.org',
        'country_code' => 'de',
    ];
}

it('sends guests to the login and forbids members', function () {
    $this->get(route('admin.people.index'))->assertRedirect(route('login'));

    $member = paUser(RoleKey::Member);
    $person = Person::factory()->create();

    $this->actingAs($member)->get(route('admin.people.index'))->assertForbidden();
    $this->get(route('admin.people.show', $person))->assertForbidden();
    $this->get(route('admin.people.create'))->assertForbidden();
});

it('lets the board view but not change people', function () {
    $board = paUser(RoleKey::BoardMember);
    $person = Person::factory()->create();

    $this->actingAs($board)->get(route('admin.people.index'))->assertOk();
    $this->get(route('admin.people.show', $person))->assertOk()->assertDontSee(route('admin.people.edit', $person));
    $this->get(route('admin.people.create'))->assertForbidden();
    $this->put(route('admin.people.update', $person), paInput())->assertForbidden();
    $this->delete(route('admin.people.destroy', $person))->assertForbidden();
});

it('lists, searches and filters people', function () {
    $staff = paUser(RoleKey::AdministrationStaff);
    Person::factory()->create(['first_name' => 'Zora', 'last_name' => 'Zebra', 'city' => 'Leipzig']);
    Person::factory()->create(['first_name' => 'Anton', 'last_name' => 'Adler', 'city' => 'Dresden']);

    $this->actingAs($staff)->get(route('admin.people.index', ['q' => 'Leipzig']))
        ->assertOk()->assertSee('Zora Zebra')->assertDontSee('Anton Adler');

    $this->get(route('admin.people.index', ['account' => 'with']))->assertOk()->assertDontSee('Zora Zebra');
    $this->get(route('admin.people.index', ['account' => 'without', 'sort' => 'city', 'direction' => 'desc']))->assertOk()->assertSee('Zora Zebra');
});

it('creates a person with normalized values', function () {
    $staff = paUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('admin.people.store'), paInput(['phone' => '']))->assertRedirect();

    $person = Person::query()->where('last_name', 'Musterfrau')->firstOrFail();

    expect($person->email)->toBe('erika@example.org')
        ->and($person->country_code)->toBe('DE')
        ->and($person->phone)->toBeNull();
});

it('validates input and rejects duplicate emails', function () {
    $staff = paUser(RoleKey::AdministrationStaff);
    Person::factory()->create(['email' => 'vorhanden@example.org']);

    $this->actingAs($staff)->post(route('admin.people.store'), paInput(['email' => 'vorhanden@example.org']))->assertSessionHasErrors('email');
    $this->post(route('admin.people.store'), paInput(['birth_date' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('birth_date');
    $this->post(route('admin.people.store'), paInput(['country_code' => 'Deutschland']))->assertSessionHasErrors('country_code');
    $this->post(route('admin.people.store'), paInput(['phone' => 'abc']))->assertSessionHasErrors('phone');
    $this->post(route('admin.people.store'), [])->assertSessionHasErrors(['first_name', 'last_name', 'birth_date', 'email']);
});

it('updates a person without account including the email', function () {
    $staff = paUser(RoleKey::AdministrationStaff);
    $person = Person::factory()->create();

    $this->actingAs($staff)->put(route('admin.people.update', $person), paInput(['email' => 'neu@example.org', 'city' => 'Berlin']))
        ->assertRedirect(route('admin.people.show', $person));

    expect($person->fresh()->email)->toBe('neu@example.org')->and($person->fresh()->city)->toBe('Berlin');
});

it('keeps the email of a person with an account', function () {
    $staff = paUser(RoleKey::AdministrationStaff);
    $target = paUser();
    $original = $target->person->email;

    $this->actingAs($staff)->get(route('admin.people.edit', $target->person))->assertOk();
    $this->put(route('admin.people.update', $target->person), paInput(['email' => 'anders@example.org']))->assertRedirect();

    expect($target->person->fresh()->email)->toBe($original)->and($target->person->fresh()->last_name)->toBe('Musterfrau');
});

it('deletes only people without an account', function () {
    $staff = paUser(RoleKey::AdministrationStaff);
    $free = Person::factory()->create();
    $linked = paUser()->person;

    $this->actingAs($staff)->delete(route('admin.people.destroy', $free))->assertRedirect(route('admin.people.index'));
    expect(Person::query()->whereKey($free->id)->exists())->toBeFalse();

    $this->delete(route('admin.people.destroy', $linked))->assertRedirect(route('admin.people.show', $linked))->assertSessionHas('warning');
    expect(Person::query()->whereKey($linked->id)->exists())->toBeTrue();
});

it('creates an invited account for a person', function () {
    Mail::fake();
    $staff = paUser(RoleKey::AdministrationStaff);
    $person = Person::factory()->create(['email' => 'konto@example.org']);

    $this->actingAs($staff)->post(route('admin.people.account', $person))->assertRedirect(route('admin.people.show', $person))->assertSessionHas('success');

    $user = User::query()->where('email', 'konto@example.org')->firstOrFail();
    expect($user->person_id)->toBe($person->id)->and($user->status)->toBe(UserStatus::PendingVerification);
    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('konto@example.org'));

    $this->post(route('admin.people.account', $person))->assertSessionHas('warning');
    expect(User::query()->where('email', 'konto@example.org')->count())->toBe(1);
});

it('refuses account creation for a person whose email is taken by another account', function () {
    Mail::fake();
    $staff = paUser(RoleKey::AdministrationStaff);
    $person = Person::factory()->create(['email' => 'doppelt@example.org']);
    User::factory()->create(['email' => 'doppelt@example.org']);

    $this->actingAs($staff)->post(route('admin.people.account', $person))->assertSessionHas('warning');
    Mail::assertNothingSent();
});
