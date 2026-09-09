<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/bestaetigung',
    'design.pages.elemente.bestaetigung',
)
    ->name('elemente.bestaetigung')
    ->defaults(
        'design_title',
        'Bestätigung & Gefahr',
    );
