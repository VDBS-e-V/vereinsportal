<?php

/** Manifest des Design-Pakets „Karten“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Karten',
    'description' => 'Karten, Teaser, Schnellzugriffe und Kontaktblock.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'card',
            'title' => 'Card',
            'description' => '',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/cards/card.blade.php',
            'css' => [
                'card.css',
            ],
            'entries' => [
                'abschnitt-split-raster',
                'karten',
                'hero',
            ],
        ],
        [
            'name' => 'contact-block',
            'title' => 'Contact Block',
            'description' => 'Kontaktblock, Vorbild: Ihr Entwurf "Kontakt".',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/cards/contact-block.blade.php',
            'css' => [
                'contact-block.css',
            ],
            'entries' => [
                'startseite',
                'kontaktblock',
            ],
        ],
        [
            'name' => 'quick-link',
            'title' => 'Quick Link',
            'description' => 'Eine Kachel in <x-vdbs.cards.quick-links>. tone 1–6 = Gruppenfarbe als Akzent (Text bleibt Ink).',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/cards/quick-link.blade.php',
            'css' => [
                'quick-link.css',
            ],
            'entries' => [
                'startseite',
                'quick-links',
            ],
        ],
        [
            'name' => 'quick-links',
            'title' => 'Quick Links',
            'description' => 'Reihe von Kacheln. Kinder: <x-vdbs.cards.quick-link>. variant: default (Akzentbalken) | solid (ganze Kachel in der Gruppenfarbe, z. B. unter dem Hero).',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/cards/quick-links.blade.php',
            'css' => [
                'quick-links.css',
            ],
            'entries' => [
                'startseite',
                'quick-links',
            ],
        ],
        [
            'name' => 'teaser',
            'title' => 'Teaser',
            'description' => 'Teaser: Bild, Titel, Anreißer, Pfeil.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/cards/teaser.blade.php',
            'css' => [
                'teaser.css',
            ],
            'entries' => [
                'startseite',
                'nachrichten',
                'teaser',
            ],
        ],
    ],
];
