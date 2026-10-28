@props([
    'name',
    'label',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'success' => null,
    'required' => false,
    'optional' => false,
    'size' => 'm',
    'multiple' => false,
    'labelHidden' => false,
])

{{--
    Auswahlliste (Paket "forms").
    options      [wert => text] oder gruppiert [gruppe => [wert => text]]; alternativ eigene <option> im Slot
    placeholder  erste, nicht wählbare Option (value="")
    multiple     Mehrfachauswahl (name wird zu name[])
--}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name.'[]' : $name;
    $state = \App\Support\Forms\FieldState::make($fieldName, $id, $hint, $error, $success, (bool) $required, (bool) $optional, $errors ?? null);
    $current = \App\Support\Forms\FieldState::oldValue($fieldName, $value);
    $selected = collect(is_array($current) ? $current : [$current])->filter(fn ($v) => $v !== null)->map(fn ($v) => (string) $v)->all();
    $describedBy = $state->describedBy();
    $isGrouped = collect($options)->contains(fn ($v) => is_array($v));
@endphp

<x-vdbs.forms.field :state="$state" :label="$label" :label-hidden="$labelHidden" {{ $attributes->only('class') }}>
    <div class="vdbs-select">
        <select id="{{ $state->id }}" name="{{ $fieldName }}"
            @if ($multiple) multiple @endif
            @if ($required) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($state->hasError()) aria-invalid="true" @endif
            {{ $attributes->except('class')->class(['vdbs-control', 'vdbs-control--select', "vdbs-control--size-{$size}", 'vdbs-control--multiple' => $multiple, 'vdbs-control--error' => $state->hasError(), 'vdbs-control--success' => $state->hasSuccess()]) }}>
            @if (filled($placeholder) && ! $multiple)
                <option value="" disabled @selected($selected === [] || $selected === [''])>{{ $placeholder }}</option>
            @endif

            @if ($slot->isNotEmpty())
                {{ $slot }}
            @elseif ($isGrouped)
                @foreach ($options as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach ($items as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selected, true))>{{ $optionLabel }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @else
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $selected, true))>{{ $optionLabel }}</option>
                @endforeach
            @endif
        </select>
        @unless ($multiple)
            <span class="vdbs-select__arrow" aria-hidden="true"><x-vdbs.icon name="chevron-down" :size="18" /></span>
        @endunless
    </div>
</x-vdbs.forms.field>
