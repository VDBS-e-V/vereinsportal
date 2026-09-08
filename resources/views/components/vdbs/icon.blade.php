@props([
    'name',
    'size' => 20,
    'label' => null,
])

@php
    $size = (int) $size;
@endphp

<svg
    {{ $attributes->class('vdbs-icon') }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if ($label === null)
        aria-hidden="true"
    @else
        role="img"
        aria-label="{{ $label }}"
    @endif
>
    @switch($name)
        @case('user')
            <circle cx="12" cy="8" r="3.25" />
            <path d="M5.5 19c.7-3.1 3.1-5 6.5-5s5.8 1.9 6.5 5" />
            @break

        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21" />
            <path d="m5.65 5.65 1.55 1.55M16.8 16.8l1.55 1.55M18.35 5.65 16.8 7.2M7.2 16.8l-1.55 1.55" />
            @break

        @case('ticket')
            <path d="M4 7.5h16v3a2 2 0 0 0 0 4v3H4v-3a2 2 0 0 0 0-4z" />
            <path d="M9 10v4M15 10v4" />
            @break

        @case('mail')
            <rect x="3" y="5" width="18" height="14" />
            <path d="m4 7 8 6 8-6" />
            @break

        @case('help')
            <circle cx="12" cy="12" r="9" />
            <path d="M9.8 9.2a2.4 2.4 0 1 1 3.4 2.2c-.9.4-1.2 1-1.2 1.8" />
            <path d="M12 17h.01" />
            @break

        @case('logout')
            <path d="M10 5H5v14h5" />
            <path d="M13 8l4 4-4 4M17 12H8" />
            @break

        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break

        @case('chevron-down')
            <path d="m7 9.5 5 5 5-5" />
            @break

        @case('external-link')
            <path d="M14 4h6v6M20 4l-9 9" />
            <path d="M18 13v6H5V6h6" />
            @break

        @case('login')
            <path d="M14 5h5v14h-5" />
            <path d="M11 8l4 4-4 4M15 12H4" />
            @break

        @case('home')
            <path d="m4 11 8-7 8 7" />
            <path d="M6 10v10h12V10M10 20v-6h4v6" />
            @break

        @default
            <rect x="5" y="5" width="14" height="14" />
    @endswitch
</svg>
