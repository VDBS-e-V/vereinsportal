<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/empty-states',
    'design.pages.elemente.empty-states',
)
    ->name('elemente.empty-states')
    ->defaults(
        'design_title',
        'Leere Zustände',
    );
