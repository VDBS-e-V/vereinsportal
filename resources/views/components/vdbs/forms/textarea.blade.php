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
    'rich' => false,
])

{{-- Mehrzeiliges Textfeld (Paket "forms"). resize: vertical | none | both. counter + maxlength: "n / max".
    rich = formatierter Text mit dem Editor (TinyMCE, selbst gehostet unter /vendor/tinymce); ohne Skript bleibt es ein normales Textfeld.
    Der Wert wird beim Speichern serverseitig bereinigt (App\Support\Html\RichText). Zähler und maxlength entfallen im Editor. --}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $resize = in_array($resize, ['vertical', 'none', 'both'], true) ? $resize : 'vertical';
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $current = \App\Support\Forms\FieldState::oldValue($name, $value ?? trim((string) $slot));
    $rich = (bool) $rich;
    $current = $rich ? \App\Support\Html\RichText::forEditor((string) $current) : $current;
    $maxlength = $rich ? null : $attributes->get('maxlength');
    $withCounter = ! $rich && $counter && filled($maxlength);
    $describedBy = $state->describedBy();
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <textarea id="{{ $state->id }}" name="{{ $name }}" rows="{{ (int) $rows }}"
        @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($state->hasError()) aria-invalid="true" @endif
        @if ($withCounter) data-vdbs-counter-target="{{ $state->id }}-counter" @endif
        @if ($rich) data-vdbs-richtext="{{ asset('vendor/tinymce') }}" @endif
        {{ $attributes->except($rich ? ['class', 'maxlength'] : 'class')->class(['vdbs-control', 'vdbs-control--textarea', "vdbs-control--size-{$size}", "vdbs-control--resize-{$resize}", 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess()]) }}>{{ $current }}</textarea>

    @if ($withCounter)
        <p class="vdbs-field__counter" id="{{ $state->id }}-counter" data-vdbs-counter="{{ $state->id }}" data-max="{{ $maxlength }}" aria-hidden="true">{{ mb_strlen((string) $current) }} / {{ $maxlength }}</p>
    @endif
</x-vdbs.forms.field>
