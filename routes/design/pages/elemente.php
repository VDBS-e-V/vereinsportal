<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente',
    'design.pages.elemente',
)
    ->name('elemente')
    ->defaults(
        'design_title',
        'Elemente',
    );
