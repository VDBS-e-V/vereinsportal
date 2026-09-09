<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeDesignNavigationGroupUser(): User
{
    return User::query()->create([
        'email' => 'design-groups-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('splits design navigation into elements and patterns', function () {
    $layout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );
    $elements = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );
    $patterns = file_get_contents(
        resource_path('views/design/pages/muster.blade.php'),
    );

    expect($layout)
        ->toContain("'navigation_group'")
        ->toContain("'muster' => 20")
        ->toContain("\$item['navigation_group']");

    expect($elements)
        ->toContain("route('design.muster')")
        ->not->toContain("route('design.elemente.artikel-news')")
        ->not->toContain("route('design.elemente.veranstaltungen')")
        ->not->toContain("route('design.elemente.kontakte')");

    expect($patterns)
        ->toContain("route('design.elemente.teaser')")
        ->toContain("route('design.elemente.medien')")
        ->toContain("route('design.elemente.artikel-news')")
        ->toContain("route('design.elemente.datensatzlisten')");
});

it('marks compound pattern routes for the pattern navigation group', function () {
    foreach ([
        'design.elemente.teaser',
        'design.elemente.medien',
        'design.elemente.artikel-news',
        'design.elemente.veranstaltungen',
        'design.elemente.kontakte',
        'design.elemente.ressourcen',
        'design.elemente.key-facts',
        'design.elemente.datensatzlisten',
    ] as $routeName) {
        $route = app('router')->getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull();
        expect($route->defaults['design_navigation_group'] ?? null)
            ->toBe('muster');
    }
});

it('renders the pattern overview and uses it in breadcrumbs', function () {
    $user = makeDesignNavigationGroupUser();

    $session = [
        'identity.session_version' => $user->session_version,
        'identity.account_validated_at' => now()->timestamp,
    ];

    $this
        ->withSession($session)
        ->actingAs($user)
        ->get('http://my.vdb.test/design/muster')
        ->assertOk()
        ->assertSeeText('Muster')
        ->assertSeeText('Artikel & News');

    $this
        ->withSession($session)
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/artikel-news')
        ->assertOk()
        ->assertSee(route('design.muster'), false)
        ->assertSeeTextInOrder([
            'Design',
            'Muster',
            'Artikel & News',
        ]);
});
