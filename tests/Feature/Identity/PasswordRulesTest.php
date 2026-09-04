<?php

use App\Modules\Identity\Support\PasswordRules;
use Illuminate\Support\Facades\Validator;

it('accepts a password that satisfies the identity policy', function () {
    $validator = Validator::make(
        ['password' => 'Sicher123!'],
        ['password' => ['required', PasswordRules::default()]],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects passwords that violate individual identity requirements', function (
    string $password,
) {
    $validator = Validator::make(
        ['password' => $password],
        ['password' => ['required', PasswordRules::default()]],
    );

    expect($validator->fails())->toBeTrue();
})->with([
    'too short' => 'Aa1!',
    'without uppercase' => 'sicher123!',
    'without lowercase' => 'SICHER123!',
    'without number' => 'SicherPass!',
    'without symbol' => 'Sicher12345',
]);
