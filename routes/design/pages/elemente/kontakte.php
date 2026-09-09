<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/kontakte',
    'design.pages.elemente.kontakte',
)
    ->name('elemente.kontakte')
    ->defaults(
        'design_title',
        'Kontakte',
    )
    ->defaults(
        'design_navigation_group',
        'muster',
    );
