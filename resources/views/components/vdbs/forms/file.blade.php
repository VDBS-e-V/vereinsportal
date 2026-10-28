@props([
    'name',
    'label',
    'id' => null,
    'accept' => null,
    'multiple' => false,
    'variant' => 'field',
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => false,
    'optional' => false,
    'labelHidden' => false,
    'prompt' => 'Datei hierher ziehen oder auswählen',
])

{{--
    Datei-Upload (Paket "forms").
    variant   field (Feld mit Auswahl-Knopf) | dropzone (gestrichelte Fläche zum Hineinziehen)
    accept    erlaubte Typen, z. B. ".pdf,image/*"; multiple = mehrere Dateien
    hint      sollte erlaubte Typen und die maximale Größe nennen
    Der Browser-Dialog bleibt erhalten (Tastatur, Screenreader); JavaScript zeigt nur die gewählten Dateinamen an.
--}}

@php
    $variant = $variant === 'dropzone' ? 'dropzone' : 'field';
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name.'[]' : $name;
    $state = \App\Support\Forms\FieldState::make($fieldName, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $describedBy = $state->describedBy($state->id.'-files');
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <div @class(['vdbs-file', "vdbs-file--{$variant}", 'vdbs-file--error' => $state->hasError()]) data-vdbs-file>
        @if ($variant === 'dropzone')
            <p class="vdbs-file__prompt"><x-vdbs.icon name="upload" :size="24" /> {{ $prompt }}</p>
        @endif

        <input id="{{ $state->id }}" name="{{ $fieldName }}" type="file"
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($multiple) multiple @endif
            @if ($required) required @endif
            aria-describedby="{{ $describedBy }}"
            @if ($state->hasError()) aria-invalid="true" @endif
            {{ $attributes->except('class')->class(['vdbs-file__input']) }}>

        <ul class="vdbs-file__list" id="{{ $state->id }}-files" aria-live="polite" data-vdbs-file-list></ul>
    </div>
</x-vdbs.forms.field>
