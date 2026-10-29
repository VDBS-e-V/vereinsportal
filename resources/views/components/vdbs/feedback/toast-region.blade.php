@props([])

{{--
    Bereich für Toasts (Paket "feedback"). Mit JavaScript schwebt er unten rechts über der Seite (Handy: unten über die
    ganze Breite), ohne JavaScript stehen die Meldungen oben im Inhalt.
--}}

<div {{ $attributes->class('vdbs-toast-region') }} data-vdbs-toast-region>{{ $slot }}</div>
