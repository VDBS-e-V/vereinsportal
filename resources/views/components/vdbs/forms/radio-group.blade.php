@props([
    'name',
    'legend',
    'options' => [],
    'value' => null,
    'layout' => 'list',
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'legendHidden' => false,
])

{{--
    Radio-Gruppe (Paket "forms"): genau eine Option.
    options  [wert => text] oder [wert => ['label' => …, 'hint' => …, 'disabled' => true]]
    layout   list (untereinander) | inline (nebeneinander) | cards (Optionskarten)
--}}

@php
    $layout = in_array($layout, ['list', 'inline', 'cards'], true) ? $layout : 'list';
    // Kein {…} innerhalb des Attribut-Bags am Komponenten-Tag: der Tag-Compiler bricht sonst
    $groupClass = 'vdbs-choice-group--'.$layout;
    $current = \App\Support\Forms\FieldState::oldValue($name, $value);
    $hasOld = request()->hasSession() && request()->session()->hasOldInput();
@endphp

<x-vdbs.forms.fieldset :legend="$legend" :name="$name" :hint="$hint" :error="$error" :required="$required" :optional="$optional" :legend-hidden="$legendHidden" {{ $attributes->class(['vdbs-choice-group', $groupClass]) }}>
    <div class="vdbs-choice-group__options">
        @foreach ($options as $optionValue => $option)
            @php
                $optionLabel = is_array($option) ? $option['label'] : $option;
                $optionHint = is_array($option) ? ($option['hint'] ?? null) : null;
                $optionDisabled = is_array($option) && ! empty($option['disabled']);
                $optionId = \App\Support\Forms\FieldState::optionId($name, $optionValue);
                $checked = (string) $current === (string) $optionValue;
            @endphp
            <div class="vdbs-choice vdbs-choice--radio">
                <div class="vdbs-choice__row">
                    <input class="vdbs-choice__input" type="radio" id="{{ $optionId }}" name="{{ $name }}" value="{{ $optionValue }}"
                        @checked($checked)
                        @if ($required) required @endif
                        @if ($optionDisabled) disabled @endif
                        @if ($optionHint) aria-describedby="{{ $optionId }}-hint" @endif>
                    <label class="vdbs-choice__label" for="{{ $optionId }}">{{ $optionLabel }}</label>
                </div>
                @if ($optionHint) <p class="vdbs-choice__hint" id="{{ $optionId }}-hint">{{ $optionHint }}</p> @endif
            </div>
        @endforeach
    </div>
</x-vdbs.forms.fieldset>
