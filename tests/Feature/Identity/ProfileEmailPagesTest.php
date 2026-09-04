<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Livewire\Volt\Volt;

function makeProfilePageUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'erika@example.test',
        'phone' => '0123456',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('protects profile and email change pages from guests', function () {
    $this
        ->get('http://my.vdb.test/profil')
        ->assertRedirect(
            route('my.login')
        );

    $this
        ->get('http://my.vdb.test/profil/email')
        ->assertRedirect(
            route('my.login')
        );
});

it('shows the own profile with email read only workflow', function () {
    $user = makeProfilePageUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/profil')
        ->assertOk()
        ->assertSee('Profil')
        ->assertSee('Erika')
        ->assertSee('erika@example.test')
        ->assertSee('E-Mail-Adresse ändern');
});

it('updates the profile through the volt page', function () {
    $user = makeProfilePageUser();

    $this->actingAs($user);

    Volt::test('identity.profile')
        ->set('last_name', 'Beispiel')
        ->set('city', 'Berlin')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $user->person->refresh();

    expect($user->person->last_name)
        ->toBe('Beispiel')
        ->and($user->person->city)
        ->toBe('Berlin');
});

it('keeps a pending email change visible when mail preparation fails', function () {
    $user = makeProfilePageUser();

    $this->actingAs($user);

    Volt::test('identity.email-change')
        ->set(
            'newEmail',
            'new@example.test',
        )
        ->call('requestChange')
        ->assertHasNoErrors()
        ->assertSet('requested', true)
        ->assertSet(
            'pendingEmail',
            'new@example.test',
        )
        ->assertSet(
            'verificationPrepared',
            false,
        );

    $user->refresh();

    expect($user->email)
        ->toBe('erika@example.test');
});
