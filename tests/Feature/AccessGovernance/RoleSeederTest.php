<?php

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use Database\Seeders\RoleSeeder;

it('seeds exactly the eight known system roles', function () {
    $this->seed(RoleSeeder::class);

    $keys = Role::query()->pluck('key')->sort()->values()->all();

    expect($keys)->toBe(collect(RoleKey::cases())
        ->map(fn (RoleKey $key) => $key->value)
        ->sort()
        ->values()
        ->all());

    expect(Role::query()->where('is_system', true)->count())->toBe(8);
});

it('is idempotent when run twice', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Role::query()->count())->toBe(8);
});
