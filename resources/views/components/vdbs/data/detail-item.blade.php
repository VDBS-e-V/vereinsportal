@props(['label', 'href' => null, 'empty' => 'nicht angegeben'])

{{-- Eintrag der Detailliste (Paket "data"). Ohne Inhalt erscheint ein Strich und für Screenreader der Text aus empty. --}}
<div {{ $attributes->class('vdbs-detail-item') }}>
    <dt class="vdbs-detail-item__label">{{ $label }}</dt>
    <dd class="vdbs-detail-item__value">
        @if ($slot->isEmpty())
            <span aria-hidden="true">–</span><span class="vdbs-sr-only">{{ $empty }}</span>
        @elseif ($href)
            <a href="{{ $href }}">{{ $slot }}</a>
        @else
            {{ $slot }}
        @endif
    </dd>
</div>
