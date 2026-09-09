<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;

function makeNavigationDataControlsDesignUser(): User
{
    return User::query()->create([
        'email' => 'design-navigation-data-'.Str::lower((string) Str::ulid()).'@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'email_verified_at' => now(),
        'session_version' => 1,
    ]);
}

it('ships local navigation tabs and pagination foundations', function () {
    $navigation = file_get_contents(
        resource_path('css/vdbs/components/navigation.css'),
    );

    expect($navigation)
        ->toContain('.local-nav__link')
        ->toContain(".local-nav__link[aria-current='page']")
        ->toContain('.page-tabs__link')
        ->toContain(".page-tabs__link[aria-current='page']")
        ->toContain('.pagination__list')
        ->toContain('.pagination__current')
        ->toContain('.pagination__disabled')
        ->toContain('@media print');
});

it('ships local search filter and table toolbar foundations', function () {
    $search = file_get_contents(
        resource_path('css/vdbs/components/search.css'),
    );
    $tables = file_get_contents(
        resource_path('css/vdbs/components/tables.css'),
    );

    expect($search)
        ->toContain('.search-form__field')
        ->toContain('.filter-bar__fields')
        ->toContain('.filter-bar__actions')
        ->toContain('@media print');

    expect($tables)
        ->toContain('.table-toolbar')
        ->toContain('.table-toolbar__summary')
        ->toContain('.table-sort')
        ->toContain('.table__actions');
});

it('documents link based tabs without fake tab widget semantics', function () {
    $page = file_get_contents(
        resource_path('views/design/pages/elemente/navigation.blade.php'),
    );

    expect($page)
        ->toContain('class="page-tabs"')
        ->toContain('aria-current="page"');

    expect(
        preg_match(
            '/<[^>]+\srole="tab(?:list)?"/i',
            $page,
        )
    )->toBe(0);
});

it('renders navigation and search filter references', function () {
    $user = makeNavigationDataControlsDesignUser();

    foreach ([
        '/design/elemente/navigation',
        '/design/elemente/suche-filter',
    ] as $uri) {
        $this
            ->withSession([
                'identity.session_version' => $user->session_version,
                'identity.account_validated_at' => now()->timestamp,
            ])
            ->actingAs($user)
            ->get('http://my.vdb.test'.$uri)
            ->assertOk();
    }
});

it('documents sortable table and optional toolbar patterns', function () {
    $tablePage = file_get_contents(
        resource_path('views/design/pages/elemente/tabellen.blade.php'),
    );

    expect($tablePage)
        ->toContain('class="table-toolbar"')
        ->toContain('aria-sort="ascending"')
        ->toContain('class="table-sort"')
        ->toContain('<x-vdbs.status')
        ->toContain('table--compact');
});

it('links the new navigation and data controls from the element overview', function () {
    $overview = file_get_contents(
        resource_path('views/design/pages/elemente.blade.php'),
    );

    expect($overview)
        ->toContain("route('design.elemente.navigation')")
        ->toContain("route('design.elemente.suche-filter')")
        ->toContain('Lokale Navigation &amp; Pagination')
        ->toContain('Suche &amp; Filter');
});
