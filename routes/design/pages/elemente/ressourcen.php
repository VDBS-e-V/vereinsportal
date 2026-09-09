<?php

use Illuminate\Support\Facades\Route;

Route::view(
    '/elemente/ressourcen',
    'design.pages.elemente.ressourcen',
)
    ->name('elemente.ressourcen')
    ->defaults(
        'design_title',
        'Ressourcen & Linklisten',
    )
    ->defaults(
        'design_navigation_group',
        'muster',
    );
