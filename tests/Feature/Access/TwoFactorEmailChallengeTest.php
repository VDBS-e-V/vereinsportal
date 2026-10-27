<?php

use App\Modules\Access\Models\TwoFactorEmailChallenge;
use App\Modules\Access\Models\User;

it('stores a hashed code and hides it when serialized', function () {
    $user = User::factory()->create();

    $challenge = TwoFactorEmailChallenge::query()->create([
        'user_id' => $user->id,
        'code_hash' => bcrypt('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    expect($challenge->toArray())->not->toHaveKey('code_hash')
        ->and($challenge->user->is($user))->toBeTrue();
});

it('tracks sent, used and invalidated timestamps', function () {
    $user = User::factory()->create();

    $challenge = TwoFactorEmailChallenge::query()->create([
        'user_id' => $user->id,
        'code_hash' => bcrypt('123456'),
        'expires_at' => now()->addMinutes(10),
        'sent_at' => now(),
    ]);

    $challenge->update(['used_at' => now()]);

    expect($challenge->sent_at)->not->toBeNull()
        ->and($challenge->used_at)->not->toBeNull()
        ->and($challenge->invalidated_at)->toBeNull();
});
