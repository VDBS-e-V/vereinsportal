<?php

use App\Modules\Access\Models\TwoFactorRecoveryCode;
use App\Modules\Access\Models\User;

it('stores a hashed recovery code and hides it when serialized', function () {
    $user = User::factory()->create();

    $code = TwoFactorRecoveryCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => bcrypt('recovery-code'),
    ]);

    expect($code->toArray())->not->toHaveKey('code_hash')
        ->and($user->twoFactorRecoveryCodes->pluck('id'))->toContain($code->id);
});

it('invalidates unused codes separately from used ones', function () {
    $user = User::factory()->create();

    $code = TwoFactorRecoveryCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => bcrypt('recovery-code'),
    ]);

    $code->update(['invalidated_at' => now()]);

    expect($code->used_at)->toBeNull()
        ->and($code->invalidated_at)->not->toBeNull();
});
