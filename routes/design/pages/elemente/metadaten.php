<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/metadaten',
    'design.pages.elemente.metadaten',
)
    ->name('elemente.metadaten')
    ->defaults(
        'design_title',
        'Status & Metadaten',
    );
