@props(['label', 'href'])

{{-- Aktiver Filter als Tag (Paket "data"): der Link entfernt den Filter. Gehört in eine Liste (<ul>), z. B. in <x-vdbs.data.toolbar>. --}}
<li {{ $attributes->class('vdbs-filter-tag') }}>
    <span class="vdbs-filter-tag__label">{{ $label }}</span>
    <a class="vdbs-filter-tag__remove" href="{{ $href }}" aria-label="Filter entfernen: {{ $label }}"><x-vdbs.icon name="x" :size="16" /></a>
</li>
