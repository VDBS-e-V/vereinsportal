<?php

/** Manifest des Design-Pakets „Inhalte“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Inhalte',
    'description' => 'Fließtext, Zitat, Download- und Terminkarte, Feature.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'download-card',
            'title' => 'Download Card',
            'description' => 'Download-Karte: Datei mit Titel, Typ und Größe. Der Titel ist der einzige Link; die ganze Karte ist klickbar.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/content/download-card.blade.php',
            'css' => [
                'download-card.css',
            ],
            'entries' => [
                'download-karte',
            ],
        ],
        [
            'name' => 'event-card',
            'title' => 'Event Card',
            'description' => 'Termin-Karte: Datum als Kachel, Titel, Zeit und Ort. Der Titel ist der einzige Link.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/content/event-card.blade.php',
            'css' => [
                'event-card.css',
            ],
            'entries' => [
                'termin-karte',
            ],
        ],
        [
            'name' => 'feature',
            'title' => 'Feature',
            'description' => 'Inhaltsblock mit Text, Bild und Aktionen.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/content/feature.blade.php',
            'css' => [
                'feature.css',
            ],
            'entries' => [
                'inhaltsblock',
            ],
        ],
        [
            'name' => 'prose',
            'title' => 'Prose',
            'description' => 'Fließtext-Stil: einheitliche Typografie für redaktionelle Texte wie Impressum, Datenschutz und Artikel.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/content/prose.blade.php',
            'css' => [
                'prose.css',
            ],
            'entries' => [
                'textstil',
            ],
        ],
        [
            'name' => 'quote',
            'title' => 'Quote',
            'description' => 'Zitat: Hervorgehobene Aussage mit Quelle, z. B. Stimmen von Schulen oder Mitgliedern.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/content/quote.blade.php',
            'css' => [
                'quote.css',
            ],
            'entries' => [
                'textstil',
            ],
        ],
    ],
];
