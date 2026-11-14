@props(['label', 'spread' => false])

{{-- Liste von Chips (Paket "data"), z. B. Bereiche oder "Oft gesucht". Kinder: <x-vdbs.data.chip>.
    spread: verteilt die Chips gleichmäßig auf ein Raster (2, 4 oder 5 Spalten) statt sie umbrechen zu lassen; so bleibt keine einzelne Zeile übrig. --}}
<nav {{ $attributes->class(['vdbs-chip-list', 'vdbs-chip-list--spread' => $spread]) }} aria-label="{{ $label }}">
    <ul class="vdbs-chip-list__list">{{ $slot }}</ul>
</nav>
