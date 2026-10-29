@props(['id', 'title', 'side' => 'end', 'size' => 'm'])

{{--
    Seitenpanel (Paket "overlays"): natives <dialog> wie das Modal, aber am Rand der Seite. Für Filter am Handy,
    Details zu einer Tabellenzeile oder kurze Formulare, ohne die Seite zu verlassen.
    Auslöser: data-vdbs-dialog-open="<id>"; Esc, Klick daneben und das Schließen-Kreuz schließen.
    side  start | end       size  s (20 rem) | m (28 rem) | l (40 rem); auf dem Handy immer volle Breite
    Slots: Standard (scrollender Inhalt), actions (feste Fußzeile)
--}}

@php
    $side = $side === 'start' ? 'start' : 'end';
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
@endphp

<dialog id="{{ $id }}" {{ $attributes->class(['vdbs-drawer', "vdbs-drawer--{$side}", "vdbs-drawer--{$size}"]) }} aria-labelledby="{{ $id }}-title" data-vdbs-dialog>
    <div class="vdbs-drawer__panel">
        <div class="vdbs-drawer__header">
            <h2 class="vdbs-drawer__title" id="{{ $id }}-title">{{ $title }}</h2>
            <button class="vdbs-modal__close" type="button" aria-label="Panel schließen" data-vdbs-dialog-close><x-vdbs.icon name="x" /></button>
        </div>
        <div class="vdbs-drawer__body">{{ $slot }}</div>
        @isset($actions)
            <div class="vdbs-drawer__actions">{{ $actions }}</div>
        @endisset
    </div>
</dialog>
