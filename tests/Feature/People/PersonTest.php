<?php

use App\Modules\Access\Models\User;
use App\Modules\People\Models\Person;
use Illuminate\Support\Carbon;

it('stores a person with casted birth date', function () {
    $person = Person::factory()->create([
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
        'birth_date' => '1990-05-12',
    ]);

    expect($person->birth_date)
        ->toBeInstanceOf(Carbon::class)
        ->and($person->birth_date->format('Y-m-d'))
        ->toBe('1990-05-12');
});

it('builds a readable full name from title, first, addition and last name', function () {
    $person = Person::factory()->create([
        'title' => 'Dr.',
        'first_name' => 'Erika',
        'name_addition' => 'von',
        'last_name' => 'Musterfrau',
    ]);

    expect($person->fullName())->toBe('Dr. Erika von Musterfrau');
});

it('omits empty name parts from the full name', function () {
    $person = Person::factory()->create([
        'title' => null,
        'first_name' => 'Erika',
        'name_addition' => null,
        'last_name' => 'Musterfrau',
    ]);

    expect($person->fullName())->toBe('Erika Musterfrau');
});

it('can exist without a user account', function () {
    $person = Person::factory()->create();

    expect($person->user)->toBeNull();
});

it('resolves its linked user account', function () {
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id]);

    expect($person->refresh()->user->is($user))->toBeTrue();
});
