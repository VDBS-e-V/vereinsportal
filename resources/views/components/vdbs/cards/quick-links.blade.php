@props(['label' => 'Schnellzugriff'])

{{-- Reihe von Kacheln (Paket "cards"). Kinder: <x-vdbs.cards.quick-link>. --}}
<nav {{ $attributes->class('vdbs-quick-links') }} aria-label="{{ $label }}">
    <ul class="vdbs-quick-links__list">{{ $slot }}</ul>
</nav>
