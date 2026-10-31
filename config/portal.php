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
     * Fassungen von Satzung und Datenschutzhinweisen, denen bei Antrag und Registrierung zugestimmt wird (Nachweis in
     * membership_consents). Bei einer neuen Fassung die Kennung ändern.
     */
    'legal' => [
        'statute_version' => env('PORTAL_STATUTE_VERSION', '2026-01'),
        'privacy_version' => env('PORTAL_PRIVACY_VERSION', '2026-01'),
        // Links zu den rechtlichen Seiten (Nutzungsbedingungen, Datenschutzerklärung), die im Zugangsantrag verlinkt werden
        'terms_url' => env('LEGAL_TERMS_URL', '/nutzungsbedingungen'),
        'privacy_url' => env('LEGAL_PRIVACY_URL', '/datenschutz'),
    ],

    /* Konto löschen: Wartefrist in Tagen zwischen Antrag und Anonymisierung (Abbruch ist bis dahin möglich). */
    'offboarding' => [
        'grace_days' => (int) env('ACCOUNT_DELETION_GRACE_DAYS', 14),
    ],

    /* Mitgliedschaften: Vorstand per E-Mail über neue (bestätigte) Online-Anträge informieren. */
    'membership' => [
        'notify_board' => (bool) env('MEMBERSHIP_NOTIFY_BOARD', true),

        /*
         * Aufbewahrung in Tagen (`php artisan membership:prune`, täglich geplant). unverified: Online-Anträge, deren
         * E-Mail-Adresse nie bestätigt wurde; rejected: abgelehnte Anträge nach der Entscheidung; ended: beendete
         * Mitgliedschaften nach dem Austritt (leer = nie löschen; die Frist legt der Verein fest).
         */
        'retention' => [
            'unverified_days' => (int) env('MEMBERSHIP_RETENTION_UNVERIFIED_DAYS', 7),
            'rejected_days' => env('MEMBERSHIP_RETENTION_REJECTED_DAYS', 180),
            'ended_days' => env('MEMBERSHIP_RETENTION_ENDED_DAYS'),
        ],
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
                ['label' => 'Impressum', 'url' => '/impressum'],
                ['label' => 'Datenschutz', 'url' => '/datenschutz'],
                ['label' => 'Barrierefreiheit', 'url' => '/barrierefreiheit'],
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
    /*
     * Schutz öffentlicher Formulare vor automatisierten Eingaben (Skripte, Bots, KI-Agenten), selbst gehostet.
     * Details: docs/51_FORMULARSCHUTZ.md. Die Tests schalten den Schutz per BOT_GUARD_ENABLED=false ab.
     */
    'bot_guard' => [
        'enabled' => env('BOT_GUARD_ENABLED', true),
        'min_seconds' => 2,       // schneller abgeschickte Formulare gelten als automatisiert
        'max_seconds' => 7200,    // älteres Token ist abgelaufen (Seite neu laden)
        'proof_of_work' => true,  // Rechenaufgabe im Browser
        'difficulty_bits' => 16,  // Nullbits der Rechenaufgabe; je 1 Bit doppelt so viel Rechenzeit
    ],

    'home' => [
        'title' => 'Willkommen im VDBS Serviceportal',
        'lead' => 'Die zentrale Anlaufstelle für Mitglieder, Teamende und Schulen des VDBS.',
        'areas' => [
            'title' => 'Das VDBS Serviceportal',
            'lead' => 'Das VDBS Serviceportal ist Ihre zentrale Anlaufstelle für alle Informationen und Dienstleistungen rund um den VDBS. Hier finden Sie alle Bereiche des Portals. Welche Bereiche Sie sehen, richtet sich nach Ihrer Rolle.',
        ],
        // Bild im Kopf der Startseite (Titel liegt mittig darüber). Die Quelle ist Pflicht; bitte durch das Originalbild samt Urheberangabe ersetzen.
        'hero_image' => [
            'src' => 'images/portal/hero-bibliothek.jpg',
            'alt' => 'Bibliothek mit mehreren Ebenen voller Bücherregale',
            'credit' => 'Bild aus dem bisherigen VDBS-Portal',
        ],
        // Bild neben dem Block „Zugang zum Portal“ (Startseite, Über das Portal); ohne src erscheint der Block ohne Bild.
        // Die Quelle ist Pflicht (Bildregel der Design-Bibliothek). Bitte Urheberin/Urheber und Lizenz ergänzen, sobald bekannt.
        'access_image' => [
            'src' => 'images/portal/zugang.jpg',
            'alt' => 'Steinerner Torbogen mit Blick in einen sonnenbeschienenen Durchgang',
            'credit' => 'Bild aus dem bisherigen VDBS-Portal',
        ],
        'access' => [
            'title' => 'Zugang zum Portal',
            'guest' => 'Hier finden Sie alle Informationen, die Sie benötigen, um auf das VDBS Serviceportal zuzugreifen. Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Wenn Sie noch kein Konto haben, können Sie hier den Zugang beantragen.',
            'member' => 'Sie sind angemeldet. Ihr Konto, Ihre Sicherheitseinstellungen und Ihre Bereiche finden Sie hier.',
        ],
        'contact' => [
            'title' => 'Kontakt',
            'text' => 'Sollten Sie Fragen oder Probleme haben, können Sie uns jederzeit kontaktieren. Wir helfen Ihnen gerne weiter. Sie erreichen uns unter den E-Mail-Adressen:',
            'image' => [
                'src' => 'images/portal/kontakt.jpg',
                'alt' => 'Leuchtendes Umschlag-Symbol auf dunklem Grund',
                'credit' => 'Bild aus dem bisherigen VDBS-Portal',
            ],
            'lines' => [
                ['label' => 'Support', 'value' => env('CONTACT_SUPPORT_EMAIL', 'support@portal.vdb.schule'), 'href' => 'mailto:'.env('CONTACT_SUPPORT_EMAIL', 'support@portal.vdb.schule')],
                ['label' => 'Kontakt', 'value' => env('CONTACT_GENERAL_EMAIL', 'kontakt@vdb.schule'), 'href' => 'mailto:'.env('CONTACT_GENERAL_EMAIL', 'kontakt@vdb.schule')],
            ],
        ],
    ],

    /*
     * Kontaktformular (öffentliche Seite „Kontakt“): Empfänger zur Auswahl, jeder mit fester E-Mail-Adresse. Die Nachricht geht nur per E-Mail
     * an diese Adresse (mit Antwort an die absendende Person); im Portal wird nichts gespeichert.
     */
    'contact_form' => [
        'recipients' => [
            'general' => ['label' => 'Allgemeine Anfrage', 'email' => env('CONTACT_GENERAL_EMAIL', 'kontakt@vdb.schule')],
            'board' => ['label' => 'Vorstand', 'email' => env('CONTACT_BOARD_EMAIL', 'vorstand@vdb.schule')],
            'support' => ['label' => 'Portal-Support', 'email' => env('CONTACT_SUPPORT_EMAIL', 'support@portal.vdb.schule')],
        ],
    ],

    /*
     * Zugangsanträge (öffentliche Seite „Zugang zum Portal“): Aufbewahrung in Tagen (entschiedene Anträge, liegengebliebene offene Anträge),
     * Hinweis an die Geschäftsstelle bei neuen Anträgen und die Fassung der Nutzungsbedingungen, der zugestimmt wird.
     */
    'access_requests' => [
        'notify_staff' => (bool) env('ACCESS_REQUESTS_NOTIFY_STAFF', true),
        'retention_days' => (int) env('ACCESS_REQUESTS_RETENTION_DAYS', 180),
        'open_retention_days' => (int) env('ACCESS_REQUESTS_OPEN_RETENTION_DAYS', 365),
        'terms_version' => '2026-01',
    ],

    /*
     * Onboarding: Willkommenspaket für neue Mitglieder (Willkommensmail mit Satzung und Willkommensbrief) beim Aufnehmen eines Online-Antrags.
     */
    'onboarding' => [
        'welcome_on_admission' => (bool) env('ONBOARDING_WELCOME_ON_ADMISSION', true),
        // Erinnerung an Teamende mit offener Einarbeitung (Befehl onboarding:remind-team, täglich geplant)
        'reminder' => ['after_days' => 14, 'repeat_days' => 14, 'max' => 3],
    ],

    /*
     * Briefkopf und Fußzeile für gedruckte Schreiben (Kostenvoranschlag). Anschrift, Bankverbindung und Kontakt des Vereins.
     */
    'letter' => [
        'logo' => 'images/brand/vdbs-briefkopf.jpg',
        'sender_line' => 'VDBS, Fritz-Karsen-Schule (K101), Onkel-Bräsig-Straße 76/78, 12359 Berlin',
        'board' => [
            'title' => 'Vereinsvorstand',
            'lines' => ['Onkel-Bräsig-Str. 76/78', '12359 Berlin'],
            'phone' => 'Tel. 030 60 900 – 168',
            'office_email' => 'neukoelln@vdb.schule',
            'website' => 'www.vdb.schule',
            'contact_email' => 'kontakt@vdb.schule',
        ],
        'footer' => [
            'organization' => 'Verband für Demokratiebildung und Bibliotheken an Schule e.V.',
            'chair_label' => 'Vorsitzender:',
            'chair' => 'Jan Ole Schmiedecke',
            'address_title' => 'Anschrift:',
            'address_lines' => ['Fritz-Karsen-Schule (VDBS)', 'Onkel-Bräsig-Str. 76/78', '12359 Berlin'],
            'contact_title' => 'Kontaktdaten:',
            'contact_lines' => ['kontakt@vdb.schule', 'vorstand@vdb.schule'],
            'bank_title' => 'VDBS e.V.',
            'bank_lines' => ['Geldinstitut: Berliner Sparkasse', 'IBAN: DE63 1005 0000 0191 2739 96', 'BIC: BELADEBEXXX'],
        ],
    ],
];
