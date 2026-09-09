<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

if (
    ! app()->environment([
        'local',
        'testing',
    ])
    && ! config('design.enabled')
) {
    return;
}

Route::middleware([
    'web',
    'auth',
    'identity.revalidate',
])
    ->domain(config('domains.my'))
    ->prefix('design')
    ->name('design.')
    ->group(function (): void {
        Route::view(
            '/',
            'design.index',
        )
            ->name('index')
            ->defaults(
                'design_title',
                'Übersicht',
            );

        $routeDirectory = base_path(
            'routes/design/pages'
        );

        if (! File::isDirectory($routeDirectory)) {
            return;
        }

        foreach (File::allFiles($routeDirectory) as $routeFile) {
            require $routeFile->getPathname();
        }
    });
