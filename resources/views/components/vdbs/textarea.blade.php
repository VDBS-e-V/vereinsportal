@props(['name', 'label', 'id' => null, 'value' => null, 'hint' => null, 'error' => null, 'success' => null])

@php
    $id ??= 'field-'.$name;
    $describedBy = collect([
        $hint ? $id.'-hint' : null,
        $error ? $id.'-error' : null,
        $success ? $id.'-success' : null,
    ])->filter()->implode(' ');
    $controlClass = [
        'vdbs-control',
        'vdbs-control--error' => (bool) $error,
        'vdbs-control--success' => ! $error && $success,
    ];
@endphp

<div class="vdbs-field">
    <label class="vdbs-field__label" for="{{ $id }}">{{ $label }}</label>
    @if ($hint) <p class="vdbs-field__hint" id="{{ $id }}-hint">{{ $hint }}</p> @endif
    <textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes->class($controlClass)->merge($describedBy ? ["aria-describedby" => $describedBy] : []) }} @if ($error) aria-invalid="true" @endif>{{ $value ?? $slot }}</textarea>
    @if ($error)
        <p class="vdbs-field__message vdbs-field__message--error" id="{{ $id }}-error"><x-vdbs.icon name="circle-alert" :size="16" />{{ $error }}</p>
    @elseif ($success)
        <p class="vdbs-field__message vdbs-field__message--success" id="{{ $id }}-success"><x-vdbs.icon name="check" :size="16" />{{ $success }}</p>
    @endif
</div>
