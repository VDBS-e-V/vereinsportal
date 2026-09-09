<?php

use App\Support\VdbsIconCatalog;
use Illuminate\Support\Facades\Route;

it('discovers icons from the central icon component', function () {
    $catalog = app(VdbsIconCatalog::class);

    expect($catalog->all()->all())
        ->toContain(
            'user',
            'calendar',
            'copy',
            'search',
        )
        ->and($catalog->search('chevron')->count())
        ->toBeGreaterThanOrEqual(3);
});

it('builds reusable icon snippets', function () {
    expect(
        app(VdbsIconCatalog::class)
            ->snippet('calendar', 20)
    )->toBe(
        '<x-vdbs.icon name="calendar" size="20" />',
    );
});

it('registers the icon browser', function () {
    expect(Route::has('design.bibliothek.icons'))
        ->toBeTrue();
});
