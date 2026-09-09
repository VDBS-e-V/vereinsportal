<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/artikel',
    'design.pages.vorlagen.artikel',
)
    ->name('vorlagen.artikel')
    ->defaults(
        'design_title',
        'Artikelseite',
    );
