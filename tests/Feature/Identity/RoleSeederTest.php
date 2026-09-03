<?php

use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\Role;
use Database\Seeders\RoleSeeder;

it('seeds all initial system roles', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->count())->toBe(8);

    foreach (RoleKey::cases() as $roleKey) {
        expect(
            Role::query()
                ->where('key', $roleKey->value)
                ->exists()
        )->toBeTrue();
    }
});

it('seeds roles idempotently', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::query()->count())->toBe(8);
});