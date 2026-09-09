<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/formulare',
    'design.pages.elemente.formulare',
)
    ->name('elemente.formulare')
    ->defaults(
        'design_title',
        'Formulare',
    );
