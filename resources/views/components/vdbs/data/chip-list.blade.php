@props(['label', 'spread' => false])

{{-- Liste von Chips (Paket "data"), z. B. Bereiche oder "Oft gesucht". Kinder: <x-vdbs.data.chip>.
    spread: Die Chips behalten ihre Breite nach Textlänge; resources/js/chip-balance.js teilt sie so auf, dass alle Zeilen ungefähr gleich breit sind (ohne JavaScript: zentriert, normaler Umbruch). --}}
<nav {{ $attributes->class(['vdbs-chip-list', 'vdbs-chip-list--spread' => $spread]) }} @if ($spread) data-vdbs-balance @endif aria-label="{{ $label }}">
    <ul class="vdbs-chip-list__list">{{ $slot }}</ul>
</nav>
