<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/dateien',
    'design.pages.elemente.dateien',
)
    ->name('elemente.dateien')
    ->defaults(
        'design_title',
        'Dateien & Uploads',
    );
