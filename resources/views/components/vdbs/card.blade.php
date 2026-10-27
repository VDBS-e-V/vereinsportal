@props(['variant' => 'standard', 'title' => null, 'source' => null])

@php
    $variant = in_array($variant, ['standard', 'info', 'statement', 'quote'], true) ? $variant : 'standard';
    $tag = $variant === 'quote' ? 'blockquote' : 'div';
@endphp

<{{ $tag }} {{ $attributes->class(['vdbs-card', "vdbs-card--{$variant}" => $variant !== 'standard']) }}>
    @if ($title) <h3 class="vdbs-card__title">{{ $title }}</h3> @endif
    {{ $slot }}
    @if ($source) <p class="vdbs-card__source">{{ $source }}</p> @endif
</{{ $tag }}>
