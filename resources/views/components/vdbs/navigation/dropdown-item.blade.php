@props(['href' => null, 'icon' => null, 'type' => 'button', 'current' => false])

{{-- Eintrag im Aufklappmenü (Paket "navigation"): Link (href) oder Button (z. B. in einem Formular zum Abmelden). --}}

@php $tag = $href !== null ? 'a' : 'button'; @endphp

<{{ $tag }} {{ $attributes->class('vdbs-dropdown__item') }} @if ($tag === 'a') href="{{ $href }}" @if ($current) aria-current="page" @endif @else type="{{ $type }}" @endif>
    @if ($icon) <x-vdbs.icon :name="$icon" :size="18" /> @endif
    <span>{{ $slot }}</span>
</{{ $tag }}>
