@props([
    'name' => 'code',
    'label' => 'Code',
    'id' => null,
    'length' => 6,
    'kind' => 'numeric',
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => true,
    'optional' => false,
    'size' => 'l',
    'labelHidden' => false,
])

{{--
    Code-Eingabe (Paket "forms") für 2FA- und E-Mail-Codes.
    length  Anzahl der Zeichen (4–10), kind: numeric | alphanumeric
    Ein einzelnes Feld mit einmaligem Code (autocomplete="one-time-code"), damit Autofill, Einfügen und
    Screenreader funktionieren. Ziffernfilter per JavaScript, ohne JS greift das Muster (pattern).
--}}

@php
    $length = max(4, min(10, (int) $length));
    $numeric = $kind !== 'alphanumeric';
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'l';
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $describedBy = $state->describedBy();
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <input id="{{ $state->id }}" name="{{ $name }}" type="text"
        autocomplete="one-time-code" autocapitalize="off" spellcheck="false"
        inputmode="{{ $numeric ? 'numeric' : 'text' }}"
        maxlength="{{ $length }}" minlength="{{ $length }}"
        pattern="{{ $numeric ? '[0-9]{'.$length.'}' : '[A-Za-z0-9]{'.$length.'}' }}"
        data-vdbs-code="{{ $numeric ? 'numeric' : 'alphanumeric' }}"
        style="--code-length: {{ $length }}"
        @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($state->hasError()) aria-invalid="true" @endif
        {{ $attributes->except('class')->class(['vdbs-control', 'vdbs-control--code', "vdbs-control--size-{$size}", 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess()]) }}>
</x-vdbs.forms.field>
