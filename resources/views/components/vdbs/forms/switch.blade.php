@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'hint' => null,
    'error' => null,
    'required' => false,
])

{{--
    Schalter (Paket "forms") für Einstellungen, die sofort gelten (Benachrichtigungen an/aus).
    Technisch eine Checkbox mit role="switch"; der Zustand steht zusätzlich als Text "An"/"Aus" neben dem Schalter,
    nicht nur als Farbe.
--}}

@php
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, null, (bool) $required, false, $errors ?? null);
    $isChecked = \App\Support\Forms\FieldState::isChecked($name, (string) $value, (bool) $checked);
    $describedBy = $state->describedBy();
@endphp

<div {{ $attributes->only('class')->class(['vdbs-choice', 'vdbs-choice--switch', 'vdbs-choice--error' => $state->hasError()]) }}>
    <div class="vdbs-choice__row">
        <input class="vdbs-choice__input" type="checkbox" role="switch" id="{{ $state->id }}" name="{{ $name }}" value="{{ $value }}"
            @checked($isChecked)
            @if ($required) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($state->hasError()) aria-invalid="true" @endif
            {{ $attributes->except('class') }}>
        <label class="vdbs-choice__label" for="{{ $state->id }}">
            <span class="vdbs-choice__text">{{ $label }}</span>
            <span class="vdbs-choice__state" aria-hidden="true"><span class="vdbs-choice__state-on">An</span><span class="vdbs-choice__state-off">Aus</span></span>
        </label>
    </div>

    @if ($state->hint)
        <p class="vdbs-choice__hint" id="{{ $state->hintId() }}">{{ $state->hint }}</p>
    @endif

    <x-vdbs.forms.messages :state="$state" />
</div>
