<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/fehlerseiten',
    'design.pages.vorlagen.fehlerseiten',
)
    ->name('vorlagen.fehlerseiten')
    ->defaults(
        'design_title',
        'Fehlerseiten',
    );
