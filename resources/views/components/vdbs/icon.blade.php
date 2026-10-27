@props(['name', 'size' => 20, 'label' => null])

@php
    $path = resource_path('icons/lucide/'.basename($name).'.svg');
    $inner = '';

    if (is_file($path)) {
        $svg = file_get_contents($path);
        $inner = preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $m) ? trim($m[1]) : '';
    }
@endphp

<svg {{ $attributes->class('vdbs-icon') }} width="{{ (int) $size }}" height="{{ (int) $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"
    @if ($label === null) aria-hidden="true" @else role="img" aria-label="{{ $label }}" @endif
>{!! $inner !!}</svg>
