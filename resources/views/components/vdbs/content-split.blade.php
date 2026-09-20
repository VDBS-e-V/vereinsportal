@props([
    'variant' => 'image',
    'side' => 'right',
    'layout' => 'balanced',
    'tone' => 'transparent',
    'width' => 'normal',
    'image' => null,
    'alt' => '',
    'caption' => null,
    'source' => null,
    'sourceUrl' => null,
    'actionListLabel' => 'Weiterführende Links',
])

@php
    if (! in_array($variant, ['image', 'actions'], true)) {
        throw new InvalidArgumentException('content-split: variant muss image oder actions sein.');
    }

    if (! in_array($side, ['left', 'right'], true)) {
        throw new InvalidArgumentException('content-split: side muss left oder right sein.');
    }

    if (! in_array($tone, ['transparent', 'subtle'], true)) {
        throw new InvalidArgumentException('content-split: tone muss transparent oder subtle sein.');
    }

    if (! in_array($width, ['normal', 'full'], true)) {
        throw new InvalidArgumentException('content-split: width muss normal oder full sein.');
    }

    $allowedLayouts = $variant === 'image'
        ? ['balanced', 'visual-dominant']
        : ['actions-compact', 'actions-wide'];

    if (! in_array($layout, $allowedLayouts, true)) {
        throw new InvalidArgumentException(
            'content-split: Das gewählte Layout passt nicht zur Variante.'
        );
    }

    if ($variant === 'image' && blank($image)) {
        throw new InvalidArgumentException('content-split: Für variant=image ist image erforderlich.');
    }

    if ($variant === 'image' && blank($caption)) {
        throw new InvalidArgumentException('content-split: Bilder benötigen eine sichtbare Beschreibung (caption).');
    }

    if ($variant === 'image' && blank($source)) {
        throw new InvalidArgumentException('content-split: Bilder benötigen eine Quelle (source).');
    }
@endphp

<section
    {{ $attributes->class([
        'content-split',
        'content-split--variant-'.$variant,
        'content-split--side-'.$side,
        'content-split--layout-'.$layout,
        'content-split--tone-'.$tone,
        'content-split--width-'.$width,
    ]) }}
>
    <div class="content-split__inner">
        <div class="content-split__content">
            @isset($heading)
                <div class="content-split__heading">
                    {{ $heading }}
                </div>
            @endisset

            <div class="content-split__body">
                {{ $slot }}
            </div>

            @isset($actions)
                <div class="content-split__actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        <div class="content-split__secondary">
            @if ($variant === 'image')
                <figure class="content-split__figure">
                    <img
                        class="content-split__image"
                        src="{{ $image }}"
                        alt="{{ $alt }}"
                    >

                    <figcaption class="content-split__caption">
                        <span class="content-split__caption-text">
                            {{ $caption }}
                        </span>

                        <span class="content-split__source">
                            Bildquelle:
                            @if ($sourceUrl)
                                <a href="{{ $sourceUrl }}">{{ $source }}</a>
                            @else
                                {{ $source }}
                            @endif
                        </span>
                    </figcaption>
                </figure>
            @else
                @isset($actionList)
                    <nav
                        class="content-split__action-nav"
                        aria-label="{{ $actionListLabel }}"
                    >
                        <div class="content-split__button-list">
                            {{ $actionList }}
                        </div>
                    </nav>
                @endisset
            @endif
        </div>
    </div>
</section>
