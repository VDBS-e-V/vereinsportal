<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeElementCategoriesDesignUser(): User
{
    $user = User::query()->create([
        'email' => 'design-elements-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('shows the element category overview', function () {
    $user = makeElementCategoriesDesignUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente')
        ->assertOk()
        ->assertSeeText('Buttons')
        ->assertSeeText('Formulare')
        ->assertSeeText('Hinweise')
        ->assertSeeText('Teaser')
        ->assertSeeText('Tabellen')
        ->assertSee(route('design.elemente.buttons'), false)
        ->assertSee(route('design.elemente.formulare'), false)
        ->assertSee(route('design.elemente.hinweise'), false)
        ->assertSee(route('design.elemente.teaser'), false)
        ->assertSee(route('design.elemente.tabellen'), false);
});

it('renders all current element category pages', function () {
    $user = makeElementCategoriesDesignUser();

    foreach ([
        'buttons',
        'formulare',
        'hinweise',
        'teaser',
        'tabellen',
    ] as $page) {
        $this
            ->withSession([
                'identity.session_version' => $user->session_version,
                'identity.account_validated_at' => now()->timestamp,
            ])
            ->actingAs($user)
            ->get('http://my.vdb.test/design/elemente/'.$page)
            ->assertOk();
    }
});

it('keeps element subpages in the horizontal design navigation hierarchy', function () {
    $layout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );

    expect($layout)
        ->toContain('$nestedDesignNavigation')
        ->toContain("'children' => \$children")
        ->not->toContain('design-sidebar');
});

it('shows the complete breadcrumb path on nested element pages', function () {
    $user = makeElementCategoriesDesignUser();

    $response = $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/design/elemente/buttons');

    $response
        ->assertOk()
        ->assertSee(route('design.index'), false)
        ->assertSee(route('design.elemente'), false)
        ->assertSeeTextInOrder([
            'Design',
            'Elemente',
            'Buttons',
        ]);
});
