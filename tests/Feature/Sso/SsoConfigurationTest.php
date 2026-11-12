<?php

use App\Modules\Access\Models\User;
use Laravel\Passport\HasApiTokens;

it('configures the api guard to use passport', function () {
    expect(config('auth.guards.api.driver'))->toBe('passport')
        ->and(config('auth.guards.api.provider'))->toBe('users');
});

it('gives the Access user model api tokens via passport', function () {
    expect(in_array(HasApiTokens::class, class_uses_recursive(User::class), true))
        ->toBeTrue();
});
