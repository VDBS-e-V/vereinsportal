<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/print',
    'design.pages.print',
)
    ->name('print')
    ->defaults(
        'design_title',
        'Print',
    );
