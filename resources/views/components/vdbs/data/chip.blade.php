@props(['label', 'href', 'icon' => null, 'mark' => null, 'markNumbered' => true])

{{-- Ein Chip als Link. mark = Gruppen-Marke 1–6 (optional), mark-numbered = false zeigt nur Form und Farbe ohne Nummer, icon = Lucide-Name (optional). --}}
<li {{ $attributes->class('vdbs-chip') }}>
    <a class="vdbs-chip__link" href="{{ $href }}">
        @if ($mark) <x-vdbs.data.group-mark :number="$mark" :numbered="$markNumbered" /> @endif
        @if ($icon) <x-vdbs.icon class="vdbs-chip__icon" :name="$icon" :size="18" /> @endif
        <span class="vdbs-chip__label">{{ $label }}</span>
    </a>
</li>
