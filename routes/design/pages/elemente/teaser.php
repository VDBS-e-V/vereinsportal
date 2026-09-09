<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/teaser',
    'design.pages.elemente.teaser',
)
    ->name('elemente.teaser')
    ->defaults(
        'design_title',
        'Teaser',
    )
    ->defaults(
        'design_navigation_group',
        'muster',
    );
