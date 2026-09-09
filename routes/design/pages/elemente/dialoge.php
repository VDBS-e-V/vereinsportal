<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/dialoge',
    'design.pages.elemente.dialoge',
)
    ->name('elemente.dialoge')
    ->defaults(
        'design_title',
        'Dialoge & Overlays',
    );
