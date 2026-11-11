@props(['label' => 'Schnellzugriff', 'variant' => 'default'])

{{-- Reihe von Kacheln (Paket "cards"). Kinder: <x-vdbs.cards.quick-link>. variant: default (Akzentbalken) | solid (ganze Kachel in der Gruppenfarbe, z. B. unter dem Hero). --}}
@php $variant = $variant === 'solid' ? 'solid' : 'default'; @endphp
<nav {{ $attributes->class(['vdbs-quick-links', "vdbs-quick-links--{$variant}"]) }} aria-label="{{ $label }}">
    <ul class="vdbs-quick-links__list">{{ $slot }}</ul>
</nav>
