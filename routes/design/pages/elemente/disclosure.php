<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/disclosure',
    'design.pages.elemente.disclosure',
)
    ->name('elemente.disclosure')
    ->defaults(
        'design_title',
        'Aufklappbare Inhalte',
    );
