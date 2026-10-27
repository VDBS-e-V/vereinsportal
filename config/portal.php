<?php

/*
 * Externe Systeme, die per SSO angebunden sind (selbst gehostet). Ein System
 * ohne URL wird in Header und Footer nicht angezeigt.
 */
return [
    'systems' => [
        ['label' => 'Nextcloud', 'url' => env('PORTAL_NEXTCLOUD_URL')],
        ['label' => 'Moodle', 'url' => env('PORTAL_MOODLE_URL')],
        ['label' => 'Umfragen', 'url' => env('PORTAL_SURVEY_URL')],
    ],

    /*
     * Öffentliche Navigation im Kopfbereich (nur für Gäste sichtbar).
     * Platzhalter '#', bis die jeweiligen Seiten existieren.
     */
    'public_links' => [
        ['label' => 'Über das Portal', 'url' => '#'],
        ['label' => 'Zugang zum Portal', 'url' => '#'],
        ['label' => 'FAQ', 'url' => '#'],
        ['label' => 'Kontakt', 'url' => '#'],
    ],

    /*
     * Footer-Spalten. Platzhalter '#', bis die Seiten bzw. Profile feststehen.
     * 'icon' ist optional (Name unter resources/icons).
     */
    'footer' => [
        [
            'heading' => 'Informationen für',
            'links' => [
                ['label' => 'Teamer:innen', 'url' => '#'],
                ['label' => 'Vereinsmitglieder:innen', 'url' => '#'],
                ['label' => 'Verwaltung', 'url' => '#'],
                ['label' => 'Schüler:innen', 'url' => '#'],
                ['label' => 'Lehrkräfte', 'url' => '#'],
                ['label' => 'Partner:innen', 'url' => '#'],
            ],
        ],
        [
            'heading' => 'Service-Portal',
            'links' => [
                ['label' => 'Startseite', 'url' => '/'],
                ['label' => 'Kontakt', 'url' => '#'],
                ['label' => 'Impressum', 'url' => '#'],
                ['label' => 'Datenschutz', 'url' => '#'],
                ['label' => 'Barrierefreiheit', 'url' => '#'],
                ['label' => 'Beratung', 'url' => '#'],
            ],
        ],
        [
            'heading' => 'Soziale Medien',
            'links' => [
                ['label' => 'Instagram', 'url' => '#', 'icon' => 'instagram'],
                ['label' => 'Homo Politicus', 'url' => '#', 'icon' => 'youtube'],
            ],
        ],
    ],
];
