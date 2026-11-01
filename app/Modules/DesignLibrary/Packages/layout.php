<?php

/** Manifest des Design-Pakets „Layout“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Layout',
    'description' => 'Container, Abschnitte, Raster, Split, Seitenkopf und Seitenlayouts.',
    'shared_css' => [
        'print.css',
        'shell.css',
    ],
    'components' => [
        [
            'name' => 'aside-layout',
            'title' => 'Aside Layout',
            'description' => 'Inhalt mit Bild, Fläche oder Seitenleiste daneben. Für Login, Anmeldung, Formulare,',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/aside-layout.blade.php',
            'css' => [
                'aside-layout.css',
            ],
            'entries' => [
                'seitenlayout',
                'login-formular',
                'nachrichten',
                'seitennavigation',
            ],
        ],
        [
            'name' => 'container',
            'title' => 'Container',
            'description' => 'Container: begrenzt die Inhaltsbreite und setzt den Seitenrand.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/container.blade.php',
            'css' => [
                'container.css',
            ],
            'entries' => [],
        ],
        [
            'name' => 'grid',
            'title' => 'Grid',
            'description' => 'Gleichmäßiges Raster: columns 2 | 3 | 4. as: div | ul (Kinder dann <li>).',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/grid.blade.php',
            'css' => [
                'grid.css',
            ],
            'entries' => [
                'startseite',
                'abschnitt-split-raster',
                'nachrichten',
                'download-karte',
                'kennzahlen',
                'teaser',
                'verwaltungsliste',
                'bilder',
                'termin-karte',
            ],
        ],
        [
            'name' => 'page-header',
            'title' => 'Page Header',
            'description' => 'Seitenkopf: Brotkrumen, Titel, Einleitung, Statusangaben und Aktionen; darunter optional Tabs.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/page-header.blade.php',
            'css' => [
                'page-header.css',
            ],
            'entries' => [
                'seitenkopf',
            ],
        ],
        [
            'name' => 'section',
            'title' => 'Section',
            'description' => 'Abschnitt einer Seite.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/section.blade.php',
            'css' => [
                'section.css',
            ],
            'entries' => [
                'startseite',
                'abschnitt-split-raster',
                'kontaktblock',
                'teaser',
            ],
        ],
        [
            'name' => 'split',
            'title' => 'Split',
            'description' => 'Text neben Bild, Vorbild: FU "Studium/Forschung", "Zugang zum Portal".',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/layout/split.blade.php',
            'css' => [
                'split.css',
            ],
            'entries' => [
                'startseite',
                'abschnitt-split-raster',
            ],
        ],
    ],
];
