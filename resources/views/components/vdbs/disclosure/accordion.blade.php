@props(['exclusive' => false])

{{--
    Akkordeon (Paket "disclosure"): aufklappbare Abschnitte für Hilfe, FAQ und lange Inhalte. Basiert auf <details>,
    funktioniert also ohne JavaScript und per Tastatur.
    exclusive  true oder ein Gruppenname: es ist immer nur ein Abschnitt offen (details name="…")
    Kinder: <x-vdbs.disclosure.accordion-item title="…">Inhalt</x-vdbs.disclosure.accordion-item>
--}}

<div {{ $attributes->class('vdbs-accordion') }}>{{ $slot }}</div>
