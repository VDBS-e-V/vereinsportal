<?php

it('builds administration pages from the existing design system patterns', function () {
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

it('links the Verwaltung area only through the central access decision', function () {
    $publicLayout = file_get_contents(
        resource_path('views/components/layouts/public.blade.php'),
    );
    $designLayout = file_get_contents(
        resource_path('views/design/layout.blade.php'),
    );

    expect($publicLayout)
        ->toContain('AdministrationAccess::class')
        ->toContain("route('administration.home')");

    expect($designLayout)
        ->toContain('AdministrationAccess::class')
        ->toContain("route('administration.home')");
});
