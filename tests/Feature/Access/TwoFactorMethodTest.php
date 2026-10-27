<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Models\TwoFactorMethod;
use App\Modules\Access\Models\User;

it('encrypts the totp secret at rest', function () {
    $user = User::factory()->create();

    $method = TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Totp,
        'secret' => 'JBSWY3DPEHPK3PXP',
        'confirmed_at' => now(),
    ]);

    $raw = \Illuminate\Support\Facades\DB::table('two_factor_methods')
        ->where('id', $method->id)
        ->value('secret');

    expect($raw)->not->toBe('JBSWY3DPEHPK3PXP')
        ->and($method->secret)->toBe('JBSWY3DPEHPK3PXP');
});

it('hides the secret when serialized', function () {
    $user = User::factory()->create();

    $method = TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
    ]);

    expect($method->toArray())->not->toHaveKey('secret');
});

it('belongs to a user', function () {
    $user = User::factory()->create();

    $method = TwoFactorMethod::query()->create([
        'user_id' => $user->id,
        'type' => TwoFactorMethodType::Email,
    ]);

    expect($method->user->is($user))->toBeTrue()
        ->and($user->twoFactorMethods->pluck('id'))->toContain($method->id);
});
