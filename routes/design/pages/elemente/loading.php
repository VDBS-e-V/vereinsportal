<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/loading',
    'design.pages.elemente.loading',
)
    ->name('elemente.loading')
    ->defaults(
        'design_title',
        'Loading & Busy',
    );
