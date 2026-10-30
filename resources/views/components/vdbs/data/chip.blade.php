@props(['label', 'href', 'icon' => null, 'mark' => null, 'markNumbered' => true, 'group' => null])

{{-- Ein Chip als Link. mark = Gruppen-Marke 1–6 (optional), mark-numbered = false zeigt nur Form und Farbe ohne Nummer, icon = Lucide-Name (optional), group = Gruppenfarbe 1–6 als Hintergrund (ohne Marke und Icon). --}}
<li {{ $attributes->class(['vdbs-chip', 'vdbs-chip--group-'.$group => $group]) }}>
    <a class="vdbs-chip__link" href="{{ $href }}">
        @if ($mark) <x-vdbs.data.group-mark :number="$mark" :numbered="$markNumbered" /> @endif
        @if ($icon) <x-vdbs.icon class="vdbs-chip__icon" :name="$icon" :size="18" /> @endif
        <span class="vdbs-chip__label">{{ $label }}</span>
    </a>
</li>
