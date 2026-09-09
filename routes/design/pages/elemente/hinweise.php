<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/hinweise',
    'design.pages.elemente.hinweise',
)
    ->name('elemente.hinweise')
    ->defaults(
        'design_title',
        'Hinweise',
    );
