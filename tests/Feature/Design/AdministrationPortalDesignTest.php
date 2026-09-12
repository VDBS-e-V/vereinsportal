<?php

it('builds internal staff pages from the existing design system patterns', function () {
    $layout = file_get_contents(
        resource_path('views/layouts/administration.blade.php'),
    );
    $index = file_get_contents(
        resource_path('views/administration/users/index.blade.php'),
    );
    $detail = file_get_contents(
        resource_path('views/administration/users/show.blade.php'),
    );

    expect($layout)
        ->toContain('<x-vdbs.portal-header')
        ->toContain("route('my.logout')")
        ->toContain("'method' => 'post'")
        ->toContain("'Verwaltung'")
        ->toContain("'Vorstand'")
        ->toContain("'Koordination'")
        ->toContain("route('board.home')")
        ->toContain("route('coordination.home')")
        ->not->toContain('sidebar');

    expect($index)
        ->toContain('class="search-form"')
        ->toContain('class="form__control"')
        ->toContain('class="table-wrapper"')
        ->toContain('class="pagination"');

    expect($detail)
        ->toContain('class="key-facts"')
        ->toContain('class="metadata-list"')
        ->toContain('class="record-list"');
});

it('links staff areas through their dedicated capabilities', function () {
    $publicLayout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );
    $designLayout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );

    foreach ([$publicLayout, $designLayout] as $layout) {
        expect($layout)
            ->toContain('AdministrationAccess::class')
            ->toContain('AdministrationCapability::AdministrationAreaAccess')
            ->toContain('AdministrationCapability::BoardAreaAccess')
            ->toContain('AdministrationCapability::CoordinationAreaAccess')
            ->toContain("route('administration.home')")
            ->toContain("route('board.home')")
            ->toContain("route('coordination.home')");
    }
});
