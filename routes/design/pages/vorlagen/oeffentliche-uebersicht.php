<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/oeffentliche-uebersicht',
    'design.pages.vorlagen.oeffentliche-uebersicht',
)
    ->name('vorlagen.oeffentliche-uebersicht')
    ->defaults(
        'design_title',
        'Öffentliche Übersichtsseite',
    );
