<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/artikel-news',
    'design.pages.elemente.artikel-news',
)
    ->name('elemente.artikel-news')
    ->defaults(
        'design_title',
        'Artikel & News',
    );
