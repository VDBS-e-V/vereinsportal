<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/fehlerseiten',
    'design.pages.fehlerseiten.index',
)
    ->name('fehlerseiten')
    ->defaults(
        'design_title',
        'Fehlerseiten',
    );
