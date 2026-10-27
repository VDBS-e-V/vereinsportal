<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\People\Models\Person;

it('creates a user with a hashed password and active status', function () {
    $user = User::factory()->create(['password' => 'Sicher123!']);

    expect($user->status)
        ->toBe(UserStatus::Active)
        ->and($user->password)
        ->not->toBe('Sicher123!')
        ->and(\Illuminate\Support\Facades\Hash::check('Sicher123!', $user->password))
        ->toBeTrue();
});

it('hides password and remember_token when serialized', function () {
    $user = User::factory()->create();

    $array = $user->toArray();

    expect($array)
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token');
});

it('can exist without a linked person', function () {
    $user = User::factory()->create();

    expect($user->person)->toBeNull();
});

it('resolves its linked person', function () {
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id]);

    expect($user->person->is($person))->toBeTrue();
});

it('has no avatar url without an avatar path', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    expect($user->avatarUrl())->toBeNull();
});
