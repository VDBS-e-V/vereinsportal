@props([
    'title',
    'titleId' => 'service-panel-heading',
    'titlePosition' => 'outside',
    'pageClass' => null,
    'panelClass' => null,
])

@php
    $titleInsidePanel = $titlePosition === 'inside';
@endphp

<div {{ $attributes->class(['mockup-page', $pageClass]) }}>
    @unless ($titleInsidePanel)
        <h1 id="{{ $titleId }}">{{ $title }}</h1>
    @endunless

    <section @class(['mockup-panel', $panelClass]) aria-labelledby="{{ $titleId }}">
        @if ($titleInsidePanel)
            <h1 id="{{ $titleId }}">{{ $title }}</h1>
        @endif

        {{ $slot }}
    </section>
</div>
