<?php

use App\Support\WebContentLibrary;
use Illuminate\Support\Facades\Route;

Route::view(
    '/bibliothek',
    'design.pages.bibliothek.index',
)
    ->name('bibliothek')
    ->defaults(
        'design_title',
        'Bibliothek',
    );

Route::view(
    '/bibliothek/code',
    'design.pages.bibliothek.code',
)
    ->name('bibliothek.code')
    ->defaults(
        'design_title',
        'Code-Vorlagen',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );

Route::view(
    '/bibliothek/icons',
    'design.pages.bibliothek.icons',
)
    ->name('bibliothek.icons')
    ->defaults(
        'design_title',
        'Icon-Browser',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );

Route::view(
    '/bibliothek/playground/buttons',
    'design.pages.bibliothek.buttons',
)
    ->name('bibliothek.buttons')
    ->defaults(
        'design_title',
        'Button-Playground',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );
Route::get(
    '/bibliothek/eintrag/{item}',
    function (
        string $item,
        WebContentLibrary $library,
    ) {
        $entry = $library->find($item);

        abort_if(
            $entry === null,
            404,
        );

        return view(
            'design.pages.bibliothek.show',
            [
                'item' => $entry,
            ],
        );
    },
)
    ->name('bibliothek.show')
    ->defaults(
        'design_title',
        'Bibliothekseintrag',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );

Route::view(
    '/bibliothek/mitmachen',
    'design.pages.bibliothek.contribute',
)
    ->name('bibliothek.contribute')
    ->defaults(
        'design_title',
        'Bibliothek erweitern',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );

Route::view(
    '/bibliothek/playground/semantik',
    'design.pages.bibliothek.semantic',
)
    ->name('bibliothek.semantic')
    ->defaults(
        'design_title',
        'Status-Playground',
    )
    ->defaults(
        'design_navigation_group',
        'bibliothek',
    );
