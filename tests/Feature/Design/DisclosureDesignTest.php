<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeDisclosureDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-disclosure-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('ships the native disclosure component styles', function () {
    $disclosure = file_get_contents(
        resource_path('css/vdbs/components/disclosure.css'),
    );
    $pages = file_get_contents(
        resource_path('css/vdbs/pages.css'),
    );

    expect($disclosure)
        ->toContain('.disclosure__summary')
        ->toContain('.disclosure__content')
        ->toContain('.disclosure[open]')
        ->toContain('@media print');

    expect($pages)
        ->not->toContain('.portal-page details')
        ->not->toContain('.portal-page summary');
});

it('uses the shared disclosure pattern on the security page', function () {
    $security = file_get_contents(
        resource_path('views/livewire/identity/security.blade.php'),
    );

    expect($security)
        ->toContain('<details class="disclosure">')
        ->toContain('class="disclosure__summary"')
        ->toContain('class="disclosure__content"');
});

it('renders the disclosure design reference', function () {
    $user = makeDisclosureDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/disclosure')
        ->assertOk()
        ->assertSeeText('Aufklappbare Inhalte')
        ->assertSeeText('Technische Details anzeigen')
        ->assertSeeText('Bereits geöffneter Zustand');
});

it('links the disclosure reference from the element overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.elemente.disclosure')")
        ->toContain('Aufklappbare Inhalte');
});
