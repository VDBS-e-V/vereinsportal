@props(['label', 'spread' => false])

{{-- Liste von Chips (Paket "data"), z. B. Bereiche oder "Oft gesucht". Kinder: <x-vdbs.data.chip>.
    spread: Die Chips behalten ihre Breite nach Textlänge und wachsen so, dass jede Zeile die volle Breite füllt; es bleibt kein loser Rest am Zeilenende. --}}
<nav {{ $attributes->class(['vdbs-chip-list', 'vdbs-chip-list--spread' => $spread]) }} aria-label="{{ $label }}">
    <ul class="vdbs-chip-list__list">{{ $slot }}</ul>
</nav>
