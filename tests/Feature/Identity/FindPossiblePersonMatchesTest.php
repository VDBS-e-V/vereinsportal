<?php

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Queries\FindPossiblePersonMatches;

it('finds only persons with at least three matching identity fields', function () {
    $fourMatches = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'country_code' => 'DE',
    ]);

    $threeMatches = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'other@example.test',
        'country_code' => 'DE',
    ]);

    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1980-05-20',
        'email' => 'unrelated@example.test',
        'country_code' => 'DE',
    ]);

    $matches = app(FindPossiblePersonMatches::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: 'erika@example.test',
    );

    expect($matches->pluck('id')->all())
        ->toContain($fourMatches->id)
        ->toContain($threeMatches->id)
        ->and($matches)->toHaveCount(2);
});

it('normalizes email before searching for possible person matches', function () {
    $person = Person::query()->create([
        'first_name' => 'Different',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'country_code' => 'DE',
    ]);

    $matches = app(FindPossiblePersonMatches::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: '  ERIKA@EXAMPLE.TEST  ',
    );

    expect($matches)
        ->toHaveCount(1)
        ->and($matches->first()->is($person))
        ->toBeTrue();
});

it('returns no possible match when no person reaches three matching fields', function () {
    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1980-05-20',
        'email' => 'other@example.test',
        'country_code' => 'DE',
    ]);

    $matches = app(FindPossiblePersonMatches::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: 'erika@example.test',
    );

    expect($matches)->toBeEmpty();
});