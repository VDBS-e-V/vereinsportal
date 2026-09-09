<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/header',
    'design.pages.header',
)
    ->name('header')
    ->defaults(
        'design_title',
        'Header',
    );
