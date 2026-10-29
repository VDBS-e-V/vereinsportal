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
     * Zwei-Faktor-Pflicht: Wer eine dieser Rollen hat, muss mindestens eine Methode einrichten (Konto > Sicherheit), bevor
     * andere Seiten hinter der Anmeldung erreichbar sind. Lokal abschaltbar mit TWO_FACTOR_REQUIRED=false.
     */
    'two_factor' => [
        'required' => (bool) env('TWO_FACTOR_REQUIRED', true),
        'roles' => ['administration', 'administration_staff', 'board_member'],
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
                ['label' => 'Kontakt', 'url' => '/kontakt'],
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

    /*
     * Bild neben dem Login-Formular (optional). Leer = Login ohne Bild.
     * Beispiel: ['src' => '/images/login.jpg', 'alt' => '', 'credit' => 'Name der Urheber:in']
     * Quelle: entweder 'credit' oder ein Eintrag in sources.json des Bildordners (siehe config/images.php).
     */
    'login_image' => null,

    /*
     * Texte der Startseite (Platzhaltertexte bis zur redaktionellen Freigabe).
     * Kontaktzeilen erscheinen nur, wenn sie hier eingetragen sind.
     */
    'home' => [
        'title' => 'Willkommen im Vereinsportal',
        'lead' => 'Die zentrale Anlaufstelle für Mitglieder, Teamende und Schulen des VDBS.',
        'areas' => [
            'title' => 'Die Bereiche des Portals',
            'lead' => 'Welche Bereiche Sie sehen, richtet sich nach Ihrer Rolle.',
        ],
        'access' => [
            'title' => 'Zugang zum Portal',
            'guest' => 'Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Ein Konto erhalten Mitglieder, Teamende und Schulen vom Verein.',
            'member' => 'Sie sind angemeldet. Ihr Konto, Ihre Sicherheitseinstellungen und Ihre Bereiche finden Sie hier.',
        ],
        'contact' => [
            'title' => 'Kontakt',
            'text' => 'Bei Fragen oder Problemen helfen wir gerne weiter.',
            'lines' => [
                // ['label' => 'Support', 'value' => 'support@example.org', 'href' => 'mailto:support@example.org'],
            ],
        ],
    ],
];
