<?php

/** Manifest des Design-Pakets „Rückmeldung“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Rückmeldung',
    'description' => 'Hinweise, Meldungen, Ladezustände, Leerzustand und Tooltip.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'alert',
            'title' => 'Alert',
            'description' => '',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/alert.blade.php',
            'css' => [
                'alert.css',
            ],
            'entries' => [
                'hinweise',
                'farben',
            ],
        ],
        [
            'name' => 'empty-state',
            'title' => 'Empty State',
            'description' => 'Leerzustand: erklärt, warum eine Liste oder Tabelle leer ist, und bietet den nächsten Schritt an.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/empty-state.blade.php',
            'css' => [
                'empty-state.css',
            ],
            'entries' => [
                'verwaltungsliste',
                'leerzustand',
            ],
        ],
        [
            'name' => 'error-page',
            'title' => 'Error Page',
            'description' => 'Fehlerseite: Fehlernummer, Titel, Erklärung und Wege zurück.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/error-page.blade.php',
            'css' => [
                'error-page.css',
            ],
            'entries' => [],
        ],
        [
            'name' => 'flash',
            'title' => 'Flash',
            'description' => 'Zeigt die Flash-Meldungen der Sitzung als Toasts, z. B. nach',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/flash.blade.php',
            'css' => [],
            'entries' => [],
        ],
        [
            'name' => 'progress',
            'title' => 'Progress',
            'description' => 'Fortschrittsbalken auf Basis von <progress>.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/progress.blade.php',
            'css' => [
                'progress.css',
            ],
            'entries' => [
                'laden',
            ],
        ],
        [
            'name' => 'skeleton',
            'title' => 'Skeleton',
            'description' => 'Platzhalter während des Ladens, z. B. Livewire-Inhalte oder nachgeladene Listen.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/skeleton.blade.php',
            'css' => [
                'skeleton.css',
            ],
            'entries' => [
                'laden',
            ],
        ],
        [
            'name' => 'toast',
            'title' => 'Toast',
            'description' => 'Toast: kurze Rückmeldung nach einer Aktion, z. B. "Gespeichert". Gehört in <x-vdbs.feedback.toast-region>.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/toast.blade.php',
            'css' => [
                'toast.css',
            ],
            'entries' => [
                'toast',
            ],
        ],
        [
            'name' => 'toast-region',
            'title' => 'Toast Region',
            'description' => 'Bereich für Toasts. Mit JavaScript schwebt er unten rechts über der Seite (Handy: unten über die',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/toast-region.blade.php',
            'css' => [],
            'entries' => [],
        ],
        [
            'name' => 'tooltip',
            'title' => 'Tooltip',
            'description' => 'Tooltip: kurze Zusatzerklärung zu einem bedienbaren Element, z. B. einem Icon-Button.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/feedback/tooltip.blade.php',
            'css' => [
                'tooltip.css',
            ],
            'entries' => [
                'tooltip',
            ],
        ],
    ],
];
