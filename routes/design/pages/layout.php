<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/layout',
    'design.pages.layout',
)
    ->name('layout')
    ->defaults(
        'design_title',
        'Layout',
    );
