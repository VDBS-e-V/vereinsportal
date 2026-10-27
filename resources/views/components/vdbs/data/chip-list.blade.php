@props(['label'])

{{-- Liste von Chips (Paket "data"), z. B. Bereiche oder "Oft gesucht". Kinder: <x-vdbs.data.chip>. --}}
<nav {{ $attributes->class('vdbs-chip-list') }} aria-label="{{ $label }}">
    <ul class="vdbs-chip-list__list">{{ $slot }}</ul>
</nav>
