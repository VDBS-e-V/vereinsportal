<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/vorlagen/veranstaltung',
    'design.pages.vorlagen.veranstaltung',
)
    ->name('vorlagen.veranstaltung')
    ->defaults(
        'design_title',
        'Veranstaltungsdetail',
    );
