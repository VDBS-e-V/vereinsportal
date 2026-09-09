<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/veranstaltungen',
    'design.pages.elemente.veranstaltungen',
)
    ->name('elemente.veranstaltungen')
    ->defaults(
        'design_title',
        'Veranstaltungen',
    );
