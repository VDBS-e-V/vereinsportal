<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen',
    'design.pages.vorlagen',
)
    ->name('vorlagen')
    ->defaults(
        'design_title',
        'Vorlagen',
    );
