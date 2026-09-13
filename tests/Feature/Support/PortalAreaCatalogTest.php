<?php

use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use App\Support\PortalAreaCatalog;

function makePortalAreaCatalogTestUser(string $email): User
{
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function grantPortalAreaCatalogTestRole(
    User $user,
    RoleKey $roleKey,
): void {
    $role = Role::query()->firstOrCreate(
        ['key' => $roleKey->value],
        [
            'name' => $roleKey->name,
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
        'ends_at' => null,
    ]);
}

it('models start and profile as existing but hidden personal areas', function () {
    $user = makePortalAreaCatalogTestUser(
        'personal-area-catalog@example.test',
    );
    $catalog = app(PortalAreaCatalog::class);

    $areas = collect($catalog->areas($user))->keyBy('key');

    expect($areas->get(PortalAreaCatalog::START))
        ->toMatchArray([
            'label' => 'Start',
            'visible_in_switcher' => false,
        ])
        ->and($areas->get(PortalAreaCatalog::PROFILE))
        ->toMatchArray([
            'label' => 'Mein Profil',
            'visible_in_switcher' => false,
        ]);

    $switcherLabels = collect($catalog->switcherAreas($user))
        ->pluck('label')
        ->all();

    expect($switcherLabels)
        ->not->toContain('Start')
        ->and($switcherLabels)
        ->not->toContain('Mein Profil');
});

it('keeps administration switcher entries capability based', function () {
    $admin = makePortalAreaCatalogTestUser(
        'administration-area-catalog@example.test',
    );
    grantPortalAreaCatalogTestRole($admin, RoleKey::Administration);

    $labels = collect(
        app(PortalAreaCatalog::class)->switcherAreas(
            $admin,
            includeDesign: false,
        ),
    )
        ->pluck('label')
        ->all();

    expect($labels)
        ->toContain('Verwaltung')
        ->toContain('Koordination')
        ->not->toContain('Vorstand');
});

it('keeps board-only users out of unrelated staff areas', function () {
    $board = makePortalAreaCatalogTestUser(
        'board-area-catalog@example.test',
    );
    grantPortalAreaCatalogTestRole($board, RoleKey::BoardMember);

    $labels = collect(
        app(PortalAreaCatalog::class)->switcherAreas(
            $board,
            includeDesign: false,
        ),
    )
        ->pluck('label')
        ->all();

    expect($labels)
        ->toBe(['Vorstand']);
});

it('combines visible staff areas when capabilities come from multiple roles', function () {
    $user = makePortalAreaCatalogTestUser(
        'combined-area-catalog@example.test',
    );
    grantPortalAreaCatalogTestRole($user, RoleKey::Administration);
    grantPortalAreaCatalogTestRole($user, RoleKey::BoardMember);

    $areas = collect(
        app(PortalAreaCatalog::class)->switcherAreas(
            $user,
            PortalAreaCatalog::BOARD,
            includeDesign: false,
        ),
    )->keyBy('key');

    expect($areas->keys()->all())
        ->toBe([
            PortalAreaCatalog::ADMINISTRATION,
            PortalAreaCatalog::BOARD,
            PortalAreaCatalog::COORDINATION,
        ])
        ->and($areas->get(PortalAreaCatalog::BOARD)['active'])
        ->toBeTrue()
        ->and($areas->get(PortalAreaCatalog::ADMINISTRATION)['active'])
        ->toBeFalse();
});

it('does not expose internal staff areas without matching capabilities', function () {
    $member = makePortalAreaCatalogTestUser(
        'member-area-catalog@example.test',
    );
    grantPortalAreaCatalogTestRole($member, RoleKey::Member);

    expect(
        app(PortalAreaCatalog::class)->switcherAreas(
            $member,
            includeDesign: false,
        ),
    )->toBe([]);
});
