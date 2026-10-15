<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/serviceportal-inhalt',
    'design.pages.vorlagen.serviceportal-inhalt',
)
    ->name('vorlagen.serviceportal-inhalt')
    ->defaults(
        'design_title',
        'Service-Portal · Inhaltsseite',
    );
