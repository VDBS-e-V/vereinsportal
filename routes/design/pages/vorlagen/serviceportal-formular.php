<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/serviceportal-formular',
    'design.pages.vorlagen.serviceportal-formular',
)
    ->name('vorlagen.serviceportal-formular')
    ->defaults(
        'design_title',
        'Service-Portal · Formularseiten',
    );
