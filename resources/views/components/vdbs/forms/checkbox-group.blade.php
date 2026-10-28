@props([
    'name',
    'legend',
    'options' => [],
    'value' => [],
    'layout' => 'list',
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'legendHidden' => false,
])

{{--
    Checkbox-Gruppe (Paket "forms"): beliebig viele Optionen, Feldname wird zu name[].
    options / layout wie bei <x-vdbs.forms.radio-group>; value ist die Liste der vorgewählten Werte.
--}}

@php
    $layout = in_array($layout, ['list', 'inline', 'cards'], true) ? $layout : 'list';
    $fieldName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $defaults = collect((array) $value)->map(fn ($v) => (string) $v)->all();
@endphp

<x-vdbs.forms.fieldset :legend="$legend" :name="$fieldName" :hint="$hint" :error="$error" :required="$required" :optional="$optional" :legend-hidden="$legendHidden" {{ $attributes->class(['vdbs-choice-group', "vdbs-choice-group--{$layout}"]) }}>
    <div class="vdbs-choice-group__options">
        @foreach ($options as $optionValue => $option)
            @php
                $optionLabel = is_array($option) ? $option['label'] : $option;
                $optionHint = is_array($option) ? ($option['hint'] ?? null) : null;
                $optionDisabled = is_array($option) && ! empty($option['disabled']);
                $optionId = \App\Support\Forms\FieldState::optionId($fieldName, $optionValue);
                $checked = \App\Support\Forms\FieldState::isChecked($fieldName, (string) $optionValue, in_array((string) $optionValue, $defaults, true));
            @endphp
            <div class="vdbs-choice vdbs-choice--checkbox">
                <div class="vdbs-choice__row">
                    <input class="vdbs-choice__input" type="checkbox" id="{{ $optionId }}" name="{{ $fieldName }}" value="{{ $optionValue }}"
                        @checked($checked)
                        @if ($optionDisabled) disabled @endif
                        @if ($optionHint) aria-describedby="{{ $optionId }}-hint" @endif>
                    <label class="vdbs-choice__label" for="{{ $optionId }}">{{ $optionLabel }}</label>
                </div>
                @if ($optionHint) <p class="vdbs-choice__hint" id="{{ $optionId }}-hint">{{ $optionHint }}</p> @endif
            </div>
        @endforeach
    </div>
</x-vdbs.forms.fieldset>
