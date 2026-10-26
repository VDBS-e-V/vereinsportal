<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/komponenten',
    'design.pages.komponenten',
)
    ->name('komponenten')
    ->defaults(
        'design_title',
        'Komponenten',
    );
