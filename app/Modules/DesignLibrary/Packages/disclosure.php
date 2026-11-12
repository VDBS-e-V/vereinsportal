<?php

/** Manifest des Design-Pakets „Aufklappen“. Wird von design:component ergänzt; Titel, Beschreibung und Status dürfen Sie von Hand ändern. */
return [
    'title' => 'Aufklappen',
    'description' => 'Akkordeon.',
    'shared_css' => [],
    'components' => [
        [
            'name' => 'accordion',
            'title' => 'Accordion',
            'description' => 'Akkordeon: aufklappbare Abschnitte für Hilfe, FAQ und lange Inhalte. Basiert auf <details>,',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/disclosure/accordion.blade.php',
            'css' => [
                'accordion.css',
            ],
            'entries' => [
                'akkordeon',
            ],
        ],
        [
            'name' => 'accordion-item',
            'title' => 'Accordion Item',
            'description' => 'Ein Abschnitt des Akkordeons. title steht in einer Überschrift der Ebene level (2–5).',
            'status' => 'beta',
            'adopted' => true,
            'redesign' => 'open',
            'blade' => 'resources/views/components/vdbs/disclosure/accordion-item.blade.php',
            'css' => [],
            'entries' => [
                'akkordeon',
            ],
        ],
    ],
];
