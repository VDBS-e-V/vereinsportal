<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/buttons',
    'design.pages.elemente.buttons',
)
    ->name('elemente.buttons')
    ->defaults(
        'design_title',
        'Buttons',
    );
