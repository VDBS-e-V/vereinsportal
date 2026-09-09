<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/verwaltung-detail',
    'design.pages.vorlagen.verwaltung-detail',
)
    ->name('vorlagen.verwaltung-detail')
    ->defaults(
        'design_title',
        'Verwaltungs-Detail',
    );
