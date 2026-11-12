<?php

/** Manifest des Design-Pakets „Grundlagen“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Grundlagen',
    'description' => 'Icons und Basis-Bausteine.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'icon',
            'title' => 'Icon',
            'description' => '',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/icon.blade.php',
            'css' => [
                'icon.css',
            ],
            'entries' => [],
        ],
    ],
];
