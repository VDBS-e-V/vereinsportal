<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/verwaltung-liste',
    'design.pages.vorlagen.verwaltung-liste',
)
    ->name('vorlagen.verwaltung-liste')
    ->defaults(
        'design_title',
        'Verwaltungs-Liste',
    );
