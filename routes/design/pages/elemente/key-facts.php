<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/key-facts',
    'design.pages.elemente.key-facts',
)
    ->name('elemente.key-facts')
    ->defaults(
        'design_title',
        'Key Facts',
    )
    ->defaults(
        'design_navigation_group',
        'muster',
    );
