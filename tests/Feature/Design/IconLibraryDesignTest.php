<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeIconDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-icons-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('imports central icon presentation styles', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );

    expect($app)
        ->toContain('components/icons.css');
});

it('ships the expanded central icon vocabulary', function () {
    $component = file_get_contents(
        resource_path('views/components/vdbs/icon.blade.php'),
    );

    foreach ([
        'search',
        'filter',
        'plus',
        'edit',
        'trash',
        'download',
        'upload',
        'file',
        'calendar',
        'clock',
        'location',
        'check',
        'alert-triangle',
        'info',
        'lock',
        'eye',
        'copy',
        'print',
        'refresh',
        'sort',
    ] as $icon) {
        expect($component)
            ->toContain("@case('{$icon}')");
    }

    expect($component)
        ->toContain('focusable="false"')
        ->toContain('aria-hidden="true"')
        ->toContain('aria-label="{{ $label }}"');
});

it('renders and links the icon design reference', function () {
    $user = makeIconDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/icons')
        ->assertOk()
        ->assertSeeText('Icons')
        ->assertSeeText('Accessibility');

    $overview = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.elemente.icons')");
});
