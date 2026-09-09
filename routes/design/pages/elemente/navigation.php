<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/navigation',
    'design.pages.elemente.navigation',
)
    ->name('elemente.navigation')
    ->defaults(
        'design_title',
        'Lokale Navigation & Pagination',
    );
