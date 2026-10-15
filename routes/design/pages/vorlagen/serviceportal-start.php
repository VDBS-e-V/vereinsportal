<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/serviceportal-start',
    'design.pages.vorlagen.serviceportal-start',
)
    ->name('vorlagen.serviceportal-start')
    ->defaults(
        'design_title',
        'Service-Portal · Startseite',
    );
