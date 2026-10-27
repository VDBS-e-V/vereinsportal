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
     * Standard: überall außer in Produktion. In Produktion erscheint stattdessen ein sichtbarer
     * Hinweis "Quelle fehlt", und der Fehler wird protokolliert.
     */
    'strict' => env('IMAGES_STRICT', ! app()->isProduction()),
];
