@props(['state', 'label', 'labelHidden' => false, 'as' => 'div'])

{{--
    Feld-Rahmen (Paket "forms", intern): Label mit Pflicht-/Optional-Kennzeichnung, Hinweis, Steuerelement (Slot)
    und Fehler-/Erfolgsmeldung. $state ist ein App\Support\Forms\FieldState.
    Pflicht wird als Text gekennzeichnet, nicht nur mit Stern (Styleguide 5.2).

    Aufbau: head (Label und Hinweis) und body (Steuerelement und Meldungen). Im Raster <x-vdbs.forms.form-grid> teilen sich
    die Felder einer Zeile die Zeilen von head und body (Subgrid): Die Steuerelemente beginnen immer auf gleicher Höhe, auch
    wenn nur ein Feld einen Hinweis hat. Sie dürfen unterschiedlich hoch enden (Textfeld neben Eingabefeld).
--}}

<div {{ $attributes->class(['vdbs-field', 'vdbs-field--error' => $state->hasError(), 'vdbs-field--success' => $state->hasSuccess()]) }}>
    <div class="vdbs-field__head">
        <label @class(['vdbs-field__label', 'vdbs-sr-only' => $labelHidden]) for="{{ $state->id }}">
            {{ $label }}
            @if ($state->required) <span class="vdbs-field__flag">(Pflichtfeld)</span>
            @elseif ($state->optional) <span class="vdbs-field__flag">(optional)</span> @endif
        </label>

        @if ($state->hint)
            <p class="vdbs-field__hint" id="{{ $state->hintId() }}">{{ $state->hint }}</p>
        @endif
    </div>

    <div class="vdbs-field__body">
        {{ $slot }}

        <x-vdbs.forms.messages :state="$state" />
    </div>
</div>
