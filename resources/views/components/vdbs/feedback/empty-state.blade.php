@props(['title', 'icon' => 'search', 'size' => 'm', 'level' => 3])

{{--
    Leerzustand (Paket "feedback"): erklärt, warum eine Liste oder Tabelle leer ist, und bietet den nächsten Schritt an.
    icon     Lucide-Name
    size     s (kompakt, z. B. in einer Karte) | m
    Slots:   Standard (Erklärung), actions (Buttons/Links)
--}}

@php
    $size = $size === 's' ? 's' : 'm';
    $tag = 'h'.max(2, min(5, (int) $level));
@endphp

<div {{ $attributes->class(['vdbs-empty', "vdbs-empty--{$size}"]) }}>
    <x-vdbs.icon class="vdbs-empty__icon" :name="$icon" :size="$size === 's' ? 28 : 40" />
    <{{ $tag }} class="vdbs-empty__title">{{ $title }}</{{ $tag }}>
    @if (! $slot->isEmpty()) <div class="vdbs-empty__text">{{ $slot }}</div> @endif
    @isset($actions)
        <div class="vdbs-empty__actions">{{ $actions }}</div>
    @endisset
</div>
