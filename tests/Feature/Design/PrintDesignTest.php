<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makePrintDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-print-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('imports the print layer after the screen design styles', function () {
    $app = file_get_contents(
        resource_path('css/app.css'),
    );

    expect($app)
        ->toContain("vdbs/print.css")
        ->and(
            strpos($app, "vdbs/print.css")
        )
        ->toBeGreaterThan(
            strpos($app, "vdbs/design.css")
        );
});

it('ships print rules for portal chrome tables and disclosure content', function () {
    $print = file_get_contents(
        resource_path('css/vdbs/print.css'),
    );

    expect($print)
        ->toContain('@media print')
        ->toContain('@page')
        ->toContain('.site-header')
        ->toContain('.vdbs-footer')
        ->toContain('thead')
        ->toContain('table-header-group')
        ->toContain('details:not([open])')
        ->toContain('break-inside: avoid');
});

it('renders the protected print design reference', function () {
    $user = makePrintDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/print')
        ->assertOk()
        ->assertSeeText('Print')
        ->assertSeeText('Druckbare Inhaltsseite')
        ->assertSeeText('Tabellen und Datensätze');
});

it('links print from the design overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/index.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.print')")
        ->toContain("route('design.muster')")
        ->toContain("route('design.layout')");
});
