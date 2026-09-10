<?php

use App\Modules\Administration\Support\PersonDataValidator;
use Illuminate\Validation\ValidationException;

it('rejects non string person fields instead of coercing them', function () {
    $validator = app(PersonDataValidator::class);

    try {
        $validator->validate([
            'title' => null,
            'first_name' => ['Erika'],
            'name_addition' => null,
            'last_name' => 'Muster',
            'birth_date' => '1990-04-12',
            'email' => 'malformed-person@example.test',
            'phone' => null,
            'street' => null,
            'house_number' => null,
            'postal_code' => null,
            'city' => null,
            'country_code' => 'DE',
        ]);
    } catch (ValidationException $exception) {
        expect($exception->errors())
            ->toHaveKey('first_name');

        return;
    }

    $this->fail('Expected malformed person data to be rejected.');
});
