<?php

/** Manifest des Design-Pakets „Medien“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Medien',
    'description' => 'Bilder, Figuren, Bildquellen und Hero.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'figure',
            'title' => 'Figure',
            'description' => 'Bild mit Unterschrift und Quelle.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/media/figure.blade.php',
            'css' => [
                'figure.css',
            ],
            'entries' => [
                'startseite',
                'abschnitt-split-raster',
                'figure',
                'kontaktblock',
                'bilder',
            ],
        ],
        [
            'name' => 'hero',
            'title' => 'Hero',
            'description' => 'Hero.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/media/hero.blade.php',
            'css' => [
                'hero.css',
            ],
            'entries' => [
                'startseite',
                'hero',
            ],
        ],
        [
            'name' => 'hero-tile',
            'title' => 'Hero Tile',
            'description' => 'Bildkachel im Hero (Variante panel, Slot "aside"): Bild, Titel und ein Link über die ganze Kachel.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/media/hero-tile.blade.php',
            'css' => [
                'hero-tile.css',
            ],
            'entries' => [
                'hero',
            ],
        ],
        [
            'name' => 'image',
            'title' => 'Image',
            'description' => 'Bild mit Pflicht-Quelle.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/media/image.blade.php',
            'css' => [
                'image.css',
            ],
            'entries' => [
                'seitenlayout',
                'login-formular',
                'bilder',
            ],
        ],
        [
            'name' => 'image-credit',
            'title' => 'Image Credit',
            'description' => 'Quellenangabe eines Bildes.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/media/image-credit.blade.php',
            'css' => [
                'image-credit.css',
            ],
            'entries' => [],
        ],
    ],
];
