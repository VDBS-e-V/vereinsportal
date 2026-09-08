<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/grundlagen',
    'design.pages.grundlagen',
)
    ->name('grundlagen')
    ->defaults(
        'design_title',
        'Grundlagen',
    );
