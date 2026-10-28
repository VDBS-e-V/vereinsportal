@props(['state', 'label', 'labelHidden' => false, 'as' => 'div'])

{{--
    Feld-Rahmen (Paket "forms", intern): Label mit Pflicht-/Optional-Kennzeichnung, Hinweis, Steuerelement (Slot)
    und Fehler-/Erfolgsmeldung. $state ist ein App\Support\Forms\FieldState.
    Pflicht wird als Text gekennzeichnet, nicht nur mit Stern (Styleguide 5.2).
--}}

<div {{ $attributes->class(['vdbs-field', 'vdbs-field--error' => $state->hasError(), 'vdbs-field--success' => $state->hasSuccess()]) }}>
    <label @class(['vdbs-field__label', 'vdbs-sr-only' => $labelHidden]) for="{{ $state->id }}">
        {{ $label }}
        @if ($state->required) <span class="vdbs-field__flag">(Pflichtfeld)</span>
        @elseif ($state->optional) <span class="vdbs-field__flag">(optional)</span> @endif
    </label>

    @if ($state->hint)
        <p class="vdbs-field__hint" id="{{ $state->hintId() }}">{{ $state->hint }}</p>
    @endif

    {{ $slot }}

    <x-vdbs.forms.messages :state="$state" />
</div>
