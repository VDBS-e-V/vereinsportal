<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/muster',
    'design.pages.muster',
)
    ->name('muster')
    ->defaults(
        'design_title',
        'Muster',
    );

Route::view(
    '/muster/media-promo',
    'design.pages.muster.media-promo',
)
    ->name('muster.media-promo')
    ->defaults(
        'design_title',
        'Media Promo',
    );
