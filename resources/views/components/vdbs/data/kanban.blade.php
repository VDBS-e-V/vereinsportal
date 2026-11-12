@props(['label' => 'Kanban-Tafel', 'token' => null])

{{--
    Kanban-Tafel (Paket "Daten"): Spalten nebeneinander, in denen Karten per Ziehen und Ablegen die Spalte wechseln.
    label   Name der Tafel für Screenreader
    token   CSRF-Token für das Verschieben (Standard: das der Sitzung)
    Kinder: <x-vdbs.data.kanban-column> mit <x-vdbs.data.kanban-card>
    Das Verschieben ist eine Verbesserung durch JavaScript (resources/js/kanban.js); jede Karte hat zusätzlich ein „Verschieben“-Formular als Alternative für Tastatur und ohne JavaScript.
--}}

<div {{ $attributes->class('vdbs-kanban') }} role="group" aria-label="{{ $label }}" data-vdbs-kanban data-token="{{ $token ?? csrf_token() }}">
    {{ $slot }}
</div>
