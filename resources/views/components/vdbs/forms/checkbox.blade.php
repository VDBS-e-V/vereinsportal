@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'indeterminate' => false,
])

{{--
    Einzelne Checkbox (Paket "forms"): "Angemeldet bleiben", Einwilligungen.
    checked         Standardzustand; nach fehlgeschlagener Validierung gilt der alte Input
    indeterminate   gemischter Zustand (z. B. Elternzeile einer Liste), nur visuell, wird per JS gesetzt
    Für Gruppen mehrerer Optionen: <x-vdbs.forms.checkbox-group>.
--}}

@php
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, null, (bool) $required, (bool) $optional, $errors ?? null);
    $isChecked = \App\Support\Forms\FieldState::isChecked($name, (string) $value, (bool) $checked);
    $describedBy = $state->describedBy();
@endphp

<div {{ $attributes->only('class')->class(['vdbs-choice', 'vdbs-choice--checkbox', 'vdbs-choice--error' => $state->hasError()]) }}>
    <div class="vdbs-choice__row">
        <input class="vdbs-choice__input" type="checkbox" id="{{ $state->id }}" name="{{ $name }}" value="{{ $value }}"
            @checked($isChecked)
            @if ($required) required @endif
            @if ($indeterminate) data-vdbs-indeterminate @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($state->hasError()) aria-invalid="true" @endif
            {{ $attributes->except('class') }}>
        <label class="vdbs-choice__label" for="{{ $state->id }}">
            {{ $label }}
            @if ($state->required) <span class="vdbs-field__flag">(Pflichtfeld)</span>
            @elseif ($state->optional) <span class="vdbs-field__flag">(optional)</span> @endif
        </label>
    </div>

    @if ($state->hint)
        <p class="vdbs-choice__hint" id="{{ $state->hintId() }}">{{ $state->hint }}</p>
    @endif

    <x-vdbs.forms.messages :state="$state" />
</div>
