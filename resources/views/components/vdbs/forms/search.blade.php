@props([
    'name' => 'q',
    'label' => 'Suche',
    'action' => null,
    'value' => null,
    'placeholder' => null,
    'button' => 'Suchen',
    'size' => 'm',
    'id' => null,
])

{{--
    Suchfeld mit Suchen-Knopf (Paket "forms"). Ein eigenes Formular (role="search", GET).
    Das Label ist für Screenreader vorhanden, visuell steht das Lupen-Icon.
--}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $fieldId = $id ?? \App\Support\Forms\FieldState::idFor($name);
    $current = \App\Support\Forms\FieldState::oldValue($name, $value ?? request()->query($name));
@endphp

<form {{ $attributes->class('vdbs-search') }} role="search" method="get" @if ($action) action="{{ $action }}" @endif>
    <label class="vdbs-sr-only" for="{{ $fieldId }}">{{ $label }}</label>
    <div @class(['vdbs-input-group', 'vdbs-search__group', "vdbs-input-group--size-{$size}"])>
        <span class="vdbs-input-group__addon"><x-vdbs.icon name="search" :size="18" /></span>
        <input id="{{ $fieldId }}" name="{{ $name }}" type="search" @if ($current !== null) value="{{ $current }}" @endif @if ($placeholder) placeholder="{{ $placeholder }}" @endif autocomplete="off"
            class="vdbs-control vdbs-control--bare vdbs-control--size-{{ $size }}">
    </div>
    <x-vdbs.actions.button type="submit" :size="$size">{{ $button }}</x-vdbs.actions.button>
</form>
