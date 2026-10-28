@props([
    'legend',
    'name' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'legendHidden' => false,
])

{{--
    Feldgruppe mit Legende (Paket "forms"): Adresse, Gruppen von Optionen.
    name verknüpft die Gruppe mit dem Validierungsfehler gleichen Namens und liefert die Id für die Sprungmarke.
--}}

@php
    $state = \App\Support\Forms\FieldState::make($name ?? \Illuminate\Support\Str::slug($legend), $id, $hint, $error, null, (bool) $required, (bool) $optional, $name ? ($errors ?? null) : null);
    $describedBy = $state->describedBy();
@endphp

<fieldset {{ $attributes->class(['vdbs-fieldset', 'vdbs-fieldset--error' => $state->hasError()]) }} id="{{ $state->id }}" tabindex="-1" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
    <legend @class(['vdbs-fieldset__legend', 'vdbs-sr-only' => $legendHidden])>
        {{ $legend }}
        @if ($state->required) <span class="vdbs-field__flag">(Pflichtfeld)</span>
        @elseif ($state->optional) <span class="vdbs-field__flag">(optional)</span> @endif
    </legend>

    @if ($state->hint)
        <p class="vdbs-field__hint" id="{{ $state->hintId() }}">{{ $state->hint }}</p>
    @endif

    <div class="vdbs-fieldset__body">{{ $slot }}</div>

    <x-vdbs.forms.messages :state="$state" />
</fieldset>
