@props(['target' => '#top', 'label' => 'Zum Seitenanfang'])

{{-- Sprung zum Seitenanfang (Paket "actions"). Ziel-ID muss auf der Seite existieren (z. B. id="top" am body). --}}
<div {{ $attributes->class('vdbs-back-to-top') }}>
    <a class="vdbs-back-to-top__link" href="{{ $target }}">
        <span class="vdbs-back-to-top__label">{{ $label }}</span>
        <x-vdbs.icon class="vdbs-back-to-top__icon" name="arrow-up" :size="18" />
    </a>
</div>
