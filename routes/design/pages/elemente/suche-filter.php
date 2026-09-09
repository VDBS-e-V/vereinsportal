<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/suche-filter',
    'design.pages.elemente.suche-filter',
)
    ->name('elemente.suche-filter')
    ->defaults(
        'design_title',
        'Suche & Filter',
    );
