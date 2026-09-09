<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/formular',
    'design.pages.vorlagen.formular',
)
    ->name('vorlagen.formular')
    ->defaults(
        'design_title',
        'Create/Edit-Formular',
    );
