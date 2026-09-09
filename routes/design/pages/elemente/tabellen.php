<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/tabellen',
    'design.pages.elemente.tabellen',
)
    ->name('elemente.tabellen')
    ->defaults(
        'design_title',
        'Tabellen',
    );
