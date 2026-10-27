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
];
