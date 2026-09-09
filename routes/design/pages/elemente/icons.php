<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/icons',
    'design.pages.elemente.icons',
)
    ->name('elemente.icons')
    ->defaults(
        'design_title',
        'Icons',
    );
