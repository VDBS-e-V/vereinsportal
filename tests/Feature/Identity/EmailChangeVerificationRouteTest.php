<?php

use App\Modules\Identity\Actions\EmailChange\StartEmailChangeAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailChangeVerificationUrl;

function makeEmailChangeRouteUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'old@example.test',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => 'old@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('completes the email change through the signed route', function () {
    $user = makeEmailChangeRouteUser();

    $change = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    $url = app(
        EmailChangeVerificationUrl::class
    )->create($change);

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(
            route('my.login')
        )
        ->assertSessionHas(
            'status',
            'Ihre neue E-Mail-Adresse wurde bestätigt. Bitte melden Sie sich erneut an.'
        );

    $this->assertGuest();

    $user->refresh();

    expect($user->email)
        ->toBe('new@example.test');
});

it('keeps the remember token invalidated after the signed email change', function () {
    $user = makeEmailChangeRouteUser();

    $user->setRememberToken('existing-remember-token');
    $user->save();

    $change = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    $url = app(
        EmailChangeVerificationUrl::class
    )->create($change);

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(
            route('my.login')
        );

    $this->assertGuest();

    $user->refresh();

    expect($user->remember_token)
        ->toBeNull();
});

it('rejects a tampered email change url', function () {
    $user = makeEmailChangeRouteUser();

    $change = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    $url = app(
        EmailChangeVerificationUrl::class
    )->create($change);

    $this
        ->get($url.'&tampered=1')
        ->assertForbidden();

    $user->refresh();

    expect($user->email)
        ->toBe('old@example.test');
});

it('rejects the signed url after the three day validity', function () {
    $user = makeEmailChangeRouteUser();

    $change = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    $url = app(
        EmailChangeVerificationUrl::class
    )->create($change);

    $this->travel(3)->days();
    $this->travel(1)->second();

    $this
        ->get($url)
        ->assertForbidden();
});
