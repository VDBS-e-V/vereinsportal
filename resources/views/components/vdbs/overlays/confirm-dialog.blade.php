@props([
    'id',
    'title',
    'action' => null,
    'method' => 'post',
    'variant' => 'default',
    'confirmLabel' => 'Bestätigen',
    'cancelLabel' => 'Abbrechen',
    'phrase' => null,
    'phraseLabel' => null,
])

{{--
    Bestätigungsdialog (Paket "overlays") für folgenreiche Aktionen, z. B. Nutzer löschen oder Konto sperren.
    Natives <dialog> wie das Modal: Esc und Klick daneben schließen, der Fokus kehrt zum Auslöser zurück
    (Auslöser: data-vdbs-dialog-open="<id>"). Der Fokus startet auf "Abbrechen", der sichere Weg ist der Standard.

    variant       default | danger   danger: rote Bestätigung mit Warn-Symbol
    action        Adresse des Formulars (method post | put | patch | delete); ohne action ist die Bestätigung ein Button,
                  den der Aufrufer im Slot "confirm" selbst liefert (z. B. für Livewire)
    phrase        Text, den man zur Bestätigung eintippen muss (z. B. LÖSCHEN). Ohne JavaScript bleibt die Bestätigung offen:
                  der Server muss die Eingabe (Feld "confirmation") zusätzlich prüfen.
    Standard-Slot: Erklärung der Folgen. Weitere Felder im Slot "fields".
--}}

@php
    $variant = $variant === 'danger' ? 'danger' : 'default';
    $verb = strtoupper($method);
    $spoof = in_array($verb, ['PUT', 'PATCH', 'DELETE'], true);
    $phraseLabel ??= $phrase ? 'Tippe „'.$phrase.'“ zur Bestätigung' : null;
    $ownConfirm = isset($confirm) && ! $confirm->isEmpty();
@endphp

<dialog id="{{ $id }}" {{ $attributes->class(['vdbs-modal', 'vdbs-modal--confirm', "vdbs-modal--{$variant}"]) }} aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-text" data-vdbs-dialog>
    <form class="vdbs-modal__form" method="{{ $spoof ? 'post' : strtolower($verb) }}" @if ($action) action="{{ $action }}" @endif>
        @if ($action)
            @if ($verb !== 'GET') @csrf @endif
            @if ($spoof) @method($verb) @endif
        @endif

        <div class="vdbs-modal__header">
            <h2 class="vdbs-modal__title" id="{{ $id }}-title">
                @if ($variant === 'danger') <x-vdbs.icon class="vdbs-modal__icon" name="triangle-alert" :size="24" /> @endif
                {{ $title }}
            </h2>
            <button class="vdbs-modal__close" type="button" aria-label="Dialog schließen" data-vdbs-dialog-close><x-vdbs.icon name="x" /></button>
        </div>

        <div class="vdbs-modal__body" id="{{ $id }}-text">
            {{ $slot }}

            @isset($fields) <div class="vdbs-modal__fields">{{ $fields }}</div> @endisset

            @if ($phrase)
                <div class="vdbs-field vdbs-modal__phrase">
                    <label class="vdbs-field__label" for="{{ $id }}-phrase">{{ $phraseLabel }}</label>
                    <input class="vdbs-control" id="{{ $id }}-phrase" name="confirmation" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" data-vdbs-confirm-phrase="{{ $phrase }}">
                </div>
            @endif
        </div>

        <div class="vdbs-modal__actions">
            <x-vdbs.actions.button type="button" variant="secondary" autofocus data-vdbs-dialog-close>{{ $cancelLabel }}</x-vdbs.actions.button>
            @if ($ownConfirm)
                {{ $confirm }}
            @else
                <x-vdbs.actions.button type="submit" :variant="$variant === 'danger' ? 'danger' : 'primary'" data-vdbs-confirm-submit>{{ $confirmLabel }}</x-vdbs.actions.button>
            @endif
        </div>
    </form>
</dialog>
