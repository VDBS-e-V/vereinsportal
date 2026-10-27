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
];
