<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\PasswordResetUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

it('creates a valid temporary signed password reset url', function () {
    $user = User::query()->create([
        'email' => 'reset-url@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $url = app(PasswordResetUrl::class)
        ->create(
            $user,
            'example-reset-token',
        );

    expect(
        URL::hasValidSignature(
            Request::create($url)
        )
    )->toBeTrue();
});

it('expires the signed password reset url after sixty minutes', function () {
    $user = User::query()->create([
        'email' => 'reset-expiry@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $url = app(PasswordResetUrl::class)
        ->create(
            $user,
            'example-reset-token',
        );

    $request = Request::create($url);

    expect(
        URL::hasValidSignature($request)
    )->toBeTrue();

    $this->travel(61)->minutes();

    expect(
        URL::hasValidSignature($request)
    )->toBeFalse();
});