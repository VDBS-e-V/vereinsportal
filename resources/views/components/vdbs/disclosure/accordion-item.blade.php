@aware(['exclusive' => false])
@props(['title', 'open' => false, 'level' => 3, 'exclusive' => false])

{{-- Ein Abschnitt des Akkordeons (Paket "disclosure"). title steht in einer Überschrift der Ebene level (2–5). --}}

@php
    $tag = 'h'.max(2, min(5, (int) $level));
    $group = $exclusive === true ? 'accordion' : (is_string($exclusive) && $exclusive !== '' ? $exclusive : null);
@endphp

<details {{ $attributes->class('vdbs-accordion__item') }} @if ($group) name="{{ $group }}" @endif @if ($open) open @endif>
    <summary class="vdbs-accordion__summary">
        <{{ $tag }} class="vdbs-accordion__title">{{ $title }}</{{ $tag }}>
        <x-vdbs.icon class="vdbs-accordion__icon" name="chevron-down" :size="20" />
    </summary>
    <div class="vdbs-accordion__panel">{{ $slot }}</div>
</details>
