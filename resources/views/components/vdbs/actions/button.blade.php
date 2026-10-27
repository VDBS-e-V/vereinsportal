@props(['variant' => 'primary', 'href' => null, 'type' => 'button', 'icon' => null])

@php
    $variant = in_array($variant, ['primary', 'secondary', 'link', 'danger'], true) ? $variant : 'primary';
    $classes = "vdbs-btn vdbs-btn--{$variant}";
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-vdbs.icon :name="$icon" /> @endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-vdbs.icon :name="$icon" /> @endif{{ $slot }}
    </button>
@endif
