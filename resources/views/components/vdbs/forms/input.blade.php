@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => false,
    'optional' => false,
    'size' => 'm',
    'prefix' => null,
    'suffix' => null,
    'prefixIcon' => null,
    'suffixIcon' => null,
    'labelHidden' => false,
    'counter' => false,
    'width' => null,
])

{{--
    Eingabefeld (Paket "forms").

    type      text | email | tel | url | number | search | date | time | datetime-local | month | week
              (Passwort: <x-vdbs.forms.password>, Code: <x-vdbs.forms.code>)
    size      s | m | l
    prefix / suffix          Text vor/nach dem Feld (z. B. "€", "kg")
    prefix-icon / suffix-icon Lucide-Name
    required / optional      Kennzeichnung als Text; required setzt auch das HTML-Attribut
    hint, error, success     Hinweis, Fehler (sonst aus dem Validierungs-Bag), Erfolg
    counter   mit maxlength: zeigt "n / max" Zeichen
    width     4 | 5 | 10 | 20 | 30   Breite nach erwartetem Inhalt in Zeichen (z. B. 5 für die Postleitzahl); ohne Angabe volle Breite
    readonly  nur lesen: gestrichelter Rand, graue Fläche und Schloss; das Feld bleibt fokussierbar, kopierbar und wird gesendet
    Alle übrigen Attribute (autocomplete, inputmode, maxlength, readonly, disabled, …) gehen an das Feld.
--}}

@php
    $type = in_array($type, ['text', 'email', 'tel', 'url', 'number', 'search', 'date', 'time', 'datetime-local', 'month', 'week'], true) ? $type : 'text';
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $state = \App\Support\Forms\FieldState::make($name, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $current = \App\Support\Forms\FieldState::oldValue($name, $value);
    $readonly = $attributes->has('readonly') && $attributes->get('readonly') !== false;
    if ($readonly && ! filled($suffixIcon)) { $suffixIcon = 'lock'; }
    $width = in_array((string) $width, ['4', '5', '10', '20', '30'], true) ? (string) $width : null;
    $grouped = filled($prefix) || filled($suffix) || filled($prefixIcon) || filled($suffixIcon);
    $maxlength = $attributes->get('maxlength');
    $withCounter = $counter && filled($maxlength);
    $classes = ['vdbs-control', "vdbs-control--size-{$size}", 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess(), 'vdbs-control--bare' => $grouped];
    $describedBy = $state->describedBy();
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class')->class([$width ? "vdbs-field--w-{$width}" : null]) }}>
    @if ($grouped)
        <div @class(['vdbs-input-group', "vdbs-input-group--size-{$size}", 'vdbs-input-group--error' => $state->hasError(), 'vdbs-input-group--success' => $state->hasSuccess(), 'vdbs-input-group--disabled' => $attributes->has('disabled'), 'vdbs-input-group--readonly' => $readonly])>
            @if (filled($prefixIcon)) <span class="vdbs-input-group__addon"><x-vdbs.icon :name="$prefixIcon" :size="18" /></span> @endif
            @if (filled($prefix)) <span class="vdbs-input-group__addon">{{ $prefix }}</span> @endif
    @endif

    <input id="{{ $state->id }}" name="{{ $name }}" type="{{ $type }}" @if ($current !== null) value="{{ $current }}" @endif
        @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($state->hasError()) aria-invalid="true" @endif
        @if ($withCounter) data-vdbs-counter-target="{{ $state->id }}-counter" @endif
        {{ $attributes->except('class')->class($classes) }}>

    @if ($grouped)
            @if (filled($suffix)) <span class="vdbs-input-group__addon">{{ $suffix }}</span> @endif
            @if (filled($suffixIcon)) <span class="vdbs-input-group__addon" @if ($readonly) title="Nur lesen" @endif><x-vdbs.icon :name="$suffixIcon" :size="18" />@if ($readonly)<span class="vdbs-sr-only">Nur lesen</span>@endif</span> @endif
        </div>
    @endif

    @if ($withCounter)
        <p class="vdbs-field__counter" id="{{ $state->id }}-counter" data-vdbs-counter="{{ $state->id }}" data-max="{{ $maxlength }}" aria-hidden="true">{{ mb_strlen((string) $current) }} / {{ $maxlength }}</p>
    @endif
</x-vdbs.forms.field>
