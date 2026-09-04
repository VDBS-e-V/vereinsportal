<?php

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Support\PersonMatchScore;

function matchingPerson(): Person
{
    return new Person([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'country_code' => 'DE',
    ]);
}

it('scores four matching identity fields', function () {
    $score = PersonMatchScore::calculate(
        person: matchingPerson(),
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: 'erika@example.test',
    );

    expect($score)->toBe(4);
});

it('treats three matching identity fields as a possible existing person', function () {
    $person = matchingPerson();

    expect(
        PersonMatchScore::isPossibleMatch(
            person: $person,
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1990-01-15',
            email: 'different@example.test',
        )
    )->toBeTrue();
});

it('does not treat two matching identity fields as an existing person match', function () {
    $person = matchingPerson();

    expect(
        PersonMatchScore::isPossibleMatch(
            person: $person,
            firstName: 'Erika',
            lastName: 'Mustermann',
            birthDate: '1985-05-20',
            email: 'different@example.test',
        )
    )->toBeFalse();
});

it('normalizes email when calculating the match score', function () {
    $score = PersonMatchScore::calculate(
        person: matchingPerson(),
        firstName: 'Wrong',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: '  ERIKA@EXAMPLE.TEST  ',
    );

    expect($score)->toBe(3);
});
