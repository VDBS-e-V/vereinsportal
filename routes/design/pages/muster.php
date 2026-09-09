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
