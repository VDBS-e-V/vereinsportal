<?php

/** Manifest des Design-Pakets „Overlays“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Overlays',
    'description' => 'Modal, Bestätigungsdialog und Seitenpanel.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'confirm-dialog',
            'title' => 'Confirm Dialog',
            'description' => 'Bestätigungsdialog für folgenreiche Aktionen, z. B. Nutzer löschen oder Konto sperren.',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/overlays/confirm-dialog.blade.php',
            'css' => [],
            'entries' => [
                'bestaetigungsdialog',
            ],
        ],
        [
            'name' => 'drawer',
            'title' => 'Drawer',
            'description' => 'Seitenpanel: natives <dialog> wie das Modal, aber am Rand der Seite. Für Filter am Handy,',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/overlays/drawer.blade.php',
            'css' => [
                'drawer.css',
            ],
            'entries' => [
                'drawer',
            ],
        ],
        [
            'name' => 'modal',
            'title' => 'Modal',
            'description' => '',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/overlays/modal.blade.php',
            'css' => [
                'modal.css',
            ],
            'entries' => [
                'modal',
            ],
        ],
    ],
];
