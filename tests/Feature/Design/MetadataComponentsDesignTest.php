<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeMetadataComponentsDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-metadata-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('ships badge status and metadata foundations', function () {
    $metadata = file_get_contents(
        resource_path('css/vdbs/components/metadata.css'),
    );
    $cards = file_get_contents(
        resource_path('css/vdbs/components/cards.css'),
    );
    $pages = file_get_contents(
        resource_path('css/vdbs/pages.css'),
    );

    expect($metadata)
        ->toContain('.badge--accent')
        ->toContain('.status--info')
        ->toContain('.status--success')
        ->toContain('.status--warning')
        ->toContain('.status--danger')
        ->toContain('.metadata-list')
        ->toContain('.portal-page__meta');

    expect($cards)
        ->not->toContain('.badge,')
        ->not->toContain('.vdbs-badge {');

    expect($pages)
        ->not->toContain('.portal-page__meta {');
});

it('ships badge and status blade components', function () {
    $badge = file_get_contents(
        resource_path('views/components/vdbs/badge.blade.php'),
    );
    $status = file_get_contents(
        resource_path('views/components/vdbs/status.blade.php'),
    );

    expect($badge)
        ->toContain("'neutral'")
        ->toContain("'accent'");

    expect($status)
        ->toContain("'neutral'")
        ->toContain("'info'")
        ->toContain("'success'")
        ->toContain("'warning'")
        ->toContain("'danger'");
});

it('renders the metadata design page', function () {
    $user = makeMetadataComponentsDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/metadaten')
        ->assertOk()
        ->assertSeeText('Status & Metadaten')
        ->assertSeeText('Mitgliedsnummer')
        ->assertSeeText('Aktiv')
        ->assertSeeText('09.09.2026, 14:30 Uhr');
});

it('links the metadata reference from the element overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.elemente.metadaten')")
        ->toContain('Status &amp; Metadaten');
});
