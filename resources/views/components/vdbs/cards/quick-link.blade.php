@props(['label', 'href', 'tone' => null, 'icon' => null])

{{-- Eine Kachel in <x-vdbs.cards.quick-links>. tone 1–6 = Gruppenfarbe als Akzent (Text bleibt Ink). --}}

@php
    $tone = $tone !== null ? max(1, min(6, (int) $tone)) : null;
@endphp

<li {{ $attributes->class(['vdbs-quick-link', "vdbs-quick-link--tone-{$tone}" => $tone !== null]) }}>
    <a class="vdbs-quick-link__link" href="{{ $href }}">
        @if ($icon) <x-vdbs.icon class="vdbs-quick-link__icon" :name="$icon" :size="24" /> @endif
        <span class="vdbs-quick-link__label">{{ $label }}</span>
    </a>
</li>
