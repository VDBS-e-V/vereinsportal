<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/medien',
    'design.pages.elemente.medien',
)
    ->name('elemente.medien')
    ->defaults(
        'design_title',
        'Medien & Abbildungen',
    );
