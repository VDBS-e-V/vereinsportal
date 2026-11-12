@props([
    'name',
    'label',
    'id' => null,
    'options' => [],
    'value' => [],
    'placeholder' => 'Bitte wählen',
    'searchable' => false,
    'searchLabel' => 'Optionen filtern',
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'size' => 'm',
])

{{--
    Mehrfachauswahl (Paket "forms"): ausgewählte Einträge stehen als Chips im Feld (mit JavaScript entfernbar),
    die Liste klappt darunter auf, optional mit Filter. Ohne JavaScript ist die Checkbox-Liste direkt sichtbar
    und bedienbar. Aufgebaut mit Checkboxen: Tastatur und Screenreader funktionieren wie bei einer Checkbox-Gruppe.

    options     [wert => text]
    value       vorgewählte Werte
    searchable  Filterfeld oberhalb der Liste
    Der Feldname wird zu name[].
--}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $defaults = collect((array) $value)->map(fn ($v) => (string) $v)->all();
    $selected = collect($options)->filter(fn ($optionLabel, $optionValue) => \App\Support\Forms\FieldState::isChecked($fieldName, (string) $optionValue, in_array((string) $optionValue, $defaults, true)));
    $state = \App\Support\Forms\FieldState::make($fieldName, $id, $hint, $error, null, (bool) $required, (bool) $optional, $errors ?? null);
    $sizeClass = 'vdbs-control--size-'.$size;
    $panelId = $state->id.'-panel';
@endphp

<x-vdbs.forms.fieldset :legend="$label" :name="$fieldName" :id="$state->id" :hint="$hint" :error="$error" :required="$required" :optional="$optional" {{ $attributes->only('class') }}>
    <div class="vdbs-multiselect" data-vdbs-multiselect>
        <div @class(['vdbs-multiselect__control', 'vdbs-control', $sizeClass, 'vdbs-control--error' => $state->hasError()])>
            <ul class="vdbs-multiselect__chips" data-vdbs-multiselect-chips>
                @foreach ($selected as $optionValue => $optionLabel)
                    <li class="vdbs-multiselect__chip">{{ $optionLabel }}</li>
                @endforeach
            </ul>
            <span class="vdbs-multiselect__placeholder vdbs-js-only" data-vdbs-multiselect-placeholder @if ($selected->isNotEmpty()) hidden @endif>{{ $placeholder }}</span>
            <button type="button" class="vdbs-multiselect__toggle vdbs-js-only" data-vdbs-multiselect-toggle aria-expanded="false" aria-controls="{{ $panelId }}" aria-label="{{ $label }}: Auswahl öffnen">
                <x-vdbs.icon name="chevron-down" :size="18" />
            </button>
        </div>

        <div class="vdbs-multiselect__panel" id="{{ $panelId }}">
            @if ($searchable)
                <input type="search" class="vdbs-control vdbs-control--size-s vdbs-multiselect__filter vdbs-js-only" data-vdbs-multiselect-filter aria-label="{{ $searchLabel }}" placeholder="{{ $searchLabel }}" autocomplete="off">
            @endif

            <div class="vdbs-multiselect__options">
                @foreach ($options as $optionValue => $optionLabel)
                    @php
                        $optionId = \App\Support\Forms\FieldState::optionId($fieldName, $optionValue);
                        $isChecked = $selected->has($optionValue);
                    @endphp
                    <div class="vdbs-choice vdbs-choice--checkbox vdbs-multiselect__option" data-vdbs-multiselect-option>
                        <div class="vdbs-choice__row">
                            <input class="vdbs-choice__input" type="checkbox" id="{{ $optionId }}" name="{{ $fieldName }}" value="{{ $optionValue }}" @checked($isChecked)>
                            <label class="vdbs-choice__label" for="{{ $optionId }}">{{ $optionLabel }}</label>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($searchable)
                <p class="vdbs-multiselect__empty" data-vdbs-multiselect-empty hidden>Keine Treffer</p>
            @endif
        </div>
    </div>
</x-vdbs.forms.fieldset>
