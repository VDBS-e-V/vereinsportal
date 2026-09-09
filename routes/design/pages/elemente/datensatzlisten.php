<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/datensatzlisten',
    'design.pages.elemente.datensatzlisten',
)
    ->name('elemente.datensatzlisten')
    ->defaults(
        'design_title',
        'Datensatzlisten',
    );
