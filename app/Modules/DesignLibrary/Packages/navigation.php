<?php

/** Manifest des Design-Pakets „Navigation“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Navigation',
    'description' => 'Kopf- und Fußbereich, Menüs, Brotkrumen, Tabs, Paginierung und Schritte.',
    'shared_css' => [
        'footer.css',
        'header.css',
        'sidebar.css',
        'theme-toggle.css',
    ],
    'components' => [
        [
            'name' => 'breadcrumb',
            'title' => 'Breadcrumb',
            'description' => '$items: list of [\'label\' => string, \'href\' => ?string]; letztes Element ist die aktuelle Seite',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/breadcrumb.blade.php',
            'css' => [
                'breadcrumb.css',
            ],
            'entries' => [
                'navigation',
            ],
        ],
        [
            'name' => 'dropdown',
            'title' => 'Dropdown',
            'description' => 'Aufklappmenü: Knopf mit einer Liste von Links und Aktionen.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/dropdown.blade.php',
            'css' => [
                'dropdown.css',
            ],
            'entries' => [
                'dropdown',
            ],
        ],
        [
            'name' => 'dropdown-item',
            'title' => 'Dropdown Item',
            'description' => 'Eintrag im Aufklappmenü: Link (href) oder Button (z. B. in einem Formular zum Abmelden).',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/dropdown-item.blade.php',
            'css' => [],
            'entries' => [
                'dropdown',
            ],
        ],
        [
            'name' => 'nav-main',
            'title' => 'Nav Main',
            'description' => '$items: list of [\'label\' => string, \'href\' => string, \'icon\' => ?string, \'current\' => bool, \'mark\' => ?int]',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/nav-main.blade.php',
            'css' => [
                'nav-main.css',
            ],
            'entries' => [
                'navigation',
            ],
        ],
        [
            'name' => 'pagination',
            'title' => 'Pagination',
            'description' => 'Paginierung: Zurück, Seitenzahlen mit Auslassung, Weiter und "Seite x von y".',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/pagination.blade.php',
            'css' => [
                'pagination.css',
            ],
            'entries' => [
                'paginierung',
                'verwaltungsliste',
            ],
        ],
        [
            'name' => 'portal-footer',
            'title' => 'Portal Footer',
            'description' => '',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/portal-footer.blade.php',
            'css' => [],
            'entries' => [],
        ],
        [
            'name' => 'portal-header',
            'title' => 'Portal Header',
            'description' => 'Kopfbereich. Zeile 1: die Bereiche des Nutzers (App\\Support\\PortalNavigation) und die Systeme; Zeile 2: Name',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/portal-header.blade.php',
            'css' => [],
            'entries' => [],
        ],
        [
            'name' => 'side-nav',
            'title' => 'Side Nav',
            'description' => 'Seitennavigation für Konto und Verwaltung; steht z. B. in <x-vdbs.layout.aside-layout variant="sidebar">.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/side-nav.blade.php',
            'css' => [
                'side-nav.css',
            ],
            'entries' => [
                'drawer',
                'seitennavigation',
            ],
        ],
        [
            'name' => 'stepper',
            'title' => 'Stepper',
            'description' => 'Schritt-Anzeige, z. B. für die Einrichtung der Zwei-Faktor-Anmeldung oder das Onboarding.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/stepper.blade.php',
            'css' => [
                'stepper.css',
            ],
            'entries' => [
                'schritte',
                'zwei-faktor-formular',
            ],
        ],
        [
            'name' => 'tab',
            'title' => 'Tab',
            'description' => 'Ein Tab-Inhalt, Kind von <x-vdbs.navigation.tabs>.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/tab.blade.php',
            'css' => [],
            'entries' => [
                'tabs',
            ],
        ],
        [
            'name' => 'tab-nav',
            'title' => 'Tab Nav',
            'description' => 'Tabs als Links: jeder Tab ist eine eigene Seite, z. B. Konto > Profil | Sicherheit | Sitzungen.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/tab-nav.blade.php',
            'css' => [
                'tab-nav.css',
            ],
            'entries' => [
                'seitenkopf',
                'tabs',
            ],
        ],
        [
            'name' => 'tabs',
            'title' => 'Tabs',
            'description' => 'Tabs mit Inhalt auf derselben Seite. Kinder: <x-vdbs.navigation.tab id="..." label="...">.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/navigation/tabs.blade.php',
            'css' => [
                'tabs.css',
            ],
            'entries' => [
                'tabs',
            ],
        ],
    ],
];
