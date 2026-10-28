@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => false,
    'optional' => false,
    'size' => 'm',
    'rows' => 5,
    'resize' => 'vertical',
    'labelHidden' => false,
    'counter' => false,
])

{{-- Mehrzeiliges Textfeld (Paket "forms"). resize: vertical | none | both. counter + maxlength: "n / max". --}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $resize = in_array($resize, ['vertical', 'none', 'both'], true) ? $resize : 'vertical';
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $current = \App\Support\Forms\FieldState::oldValue($name, $value ?? trim((string) $slot));
    $maxlength = $attributes->get('maxlength');
    $withCounter = $counter && filled($maxlength);
    $describedBy = $state->describedBy();
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <textarea id="{{ $state->id }}" name="{{ $name }}" rows="{{ (int) $rows }}"
        @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($state->hasError()) aria-invalid="true" @endif
        @if ($withCounter) data-vdbs-counter-target="{{ $state->id }}-counter" @endif
        {{ $attributes->except('class')->class(['vdbs-control', 'vdbs-control--textarea', "vdbs-control--size-{$size}", "vdbs-control--resize-{$resize}", 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess()]) }}>{{ $current }}</textarea>

    @if ($withCounter)
        <p class="vdbs-field__counter" id="{{ $state->id }}-counter" data-vdbs-counter="{{ $state->id }}" data-max="{{ $maxlength }}" aria-hidden="true">{{ mb_strlen((string) $current) }} / {{ $maxlength }}</p>
    @endif
</x-vdbs.forms.field>
