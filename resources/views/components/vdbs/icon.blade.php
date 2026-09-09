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
    focusable="false"
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

        @case('chevron-left')
            <path d="m14.5 6-6 6 6 6" />
            @break

        @case('chevron-right')
            <path d="m9.5 6 6 6-6 6" />
            @break

        @case('arrow-left')
            <path d="m10 6-6 6 6 6M4 12h16" />
            @break

        @case('arrow-right')
            <path d="m14 6 6 6-6 6M20 12H4" />
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

        @case('search')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4 4" />
            @break

        @case('filter')
            <path d="M4 5h16l-6 7v5l-4 2v-7z" />
            @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break

        @case('edit')
            <path d="M5 19h4l10-10-4-4L5 15z" />
            <path d="m13.5 6.5 4 4" />
            @break

        @case('trash')
            <path d="M5 7h14M9 7V4h6v3M7 7l1 13h8l1-13" />
            <path d="M10 11v5M14 11v5" />
            @break

        @case('download')
            <path d="M12 4v11M8 11l4 4 4-4" />
            <path d="M5 20h14" />
            @break

        @case('upload')
            <path d="M12 15V4M8 8l4-4 4 4" />
            <path d="M5 20h14" />
            @break

        @case('file')
            <path d="M6 3h8l4 4v14H6z" />
            <path d="M14 3v5h5" />
            @break

        @case('calendar')
            <rect x="4" y="5" width="16" height="15" />
            <path d="M8 3v4M16 3v4M4 10h16" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('location')
            <path d="M12 21s6-5.7 6-11a6 6 0 1 0-12 0c0 5.3 6 11 6 11z" />
            <circle cx="12" cy="10" r="2" />
            @break

        @case('check')
            <path d="m5 12 4 4 10-10" />
            @break

        @case('alert-triangle')
            <path d="M12 4 3.5 20h17z" />
            <path d="M12 9v5M12 17h.01" />
            @break

        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v6M12 7h.01" />
            @break

        @case('lock')
            <rect x="5" y="10" width="14" height="10" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break

        @case('eye')
            <path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z" />
            <circle cx="12" cy="12" r="2.5" />
            @break

        @case('copy')
            <rect x="8" y="8" width="11" height="11" />
            <path d="M16 8V5H5v11h3" />
            @break

        @case('print')
            <path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
            <rect x="7" y="14" width="10" height="6" />
            @break

        @case('refresh')
            <path d="M19 8V4l-2 2a8 8 0 1 0 2 8" />
            @break

        @case('sort')
            <path d="M8 5v14M5 8l3-3 3 3M16 19V5M13 16l3 3 3-3" />
            @break

        @case('more-horizontal')
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
            @break

        @default
            <rect x="5" y="5" width="14" height="14" />
    @endswitch
</svg>
