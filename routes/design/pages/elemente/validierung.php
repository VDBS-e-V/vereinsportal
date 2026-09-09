<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/validierung',
    'design.pages.elemente.validierung',
)
    ->name('elemente.validierung')
    ->defaults(
        'design_title',
        'Validierungsübersicht',
    );
