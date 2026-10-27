<?php

return [
    /*
     * Manifeste mit den Bildquellen (sources.json je Ordner).
     */
    'manifests' => [
        public_path('images/placeholder/sources.json'),
    ],

    /*
     * Strikter Modus: ein Bild ohne Quelle oder ohne Alt-Text wirft eine Exception.
     * Standard: überall außer bei APP_ENV=production. In Produktion erscheint stattdessen ein sichtbarer
     * Hinweis "Quelle fehlt", und der Fehler wird protokolliert.
     */
    'strict' => (bool) env('IMAGES_STRICT', env('APP_ENV', 'production') !== 'production'),
];
