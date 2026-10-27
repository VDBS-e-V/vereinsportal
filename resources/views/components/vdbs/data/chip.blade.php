@props(['label', 'href', 'icon' => null, 'mark' => null])

{{-- Ein Chip als Link. mark = Gruppen-Marke 1–6 (optional), icon = Lucide-Name (optional). --}}
<li {{ $attributes->class('vdbs-chip') }}>
    <a class="vdbs-chip__link" href="{{ $href }}">
        @if ($mark) <x-vdbs.data.group-mark :number="$mark" /> @endif
        @if ($icon) <x-vdbs.icon class="vdbs-chip__icon" :name="$icon" :size="18" /> @endif
        <span class="vdbs-chip__label">{{ $label }}</span>
    </a>
</li>
