@props(['label', 'active' => null])

{{--
    Tabs mit Inhalt auf derselben Seite (Paket "navigation"). Kinder: <x-vdbs.navigation.tab id="..." label="...">.
    Mit JavaScript entsteht daraus eine Tab-Leiste (Pfeiltasten, Pos1/Ende, #id in der Adresse wählt den Tab);
    ohne JavaScript stehen die Inhalte mit ihren Überschriften untereinander.
    label:  Beschriftung der Tab-Leiste für Screenreader (Pflicht)
    active: id des zuerst gezeigten Tabs (Standard: der erste)
--}}

<div {{ $attributes->class('vdbs-tabs') }} data-vdbs-tabs data-label="{{ $label }}" @if ($active) data-active="{{ $active }}" @endif>
    {{ $slot }}
</div>
