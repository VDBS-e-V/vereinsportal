@props([
    'code',
    'title',
    'description',
    'variant' => 'info',
    'eyebrow' => 'Fehler',
    'support' => null,
    'primaryLabel' => null,
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
    'compact' => false,
    'image' => null,
    'imageAlt' => '',
    'links' => [],
    'linksTitle' => 'Das könnte Ihnen helfen',
])

@php
    $allowedVariants = [
        'info',
        'warning',
        'danger',
        'success',
    ];

    $variant = in_array(
        $variant,
        $allowedVariants,
        true,
    )
        ? $variant
        : 'info';

    $titleId = 'vdbs-error-'.\Illuminate\Support\Str::slug(
        $code.'-'.$title,
    );

    // Weiterführende Links: Ziele, die schon als Button vorkommen, entfallen
    // (Vergleich über den Pfad); höchstens vier Links, keine doppelten Texte.
    $normalizePath = static function (?string $url): string {
        $path = parse_url((string) $url, PHP_URL_PATH);

        return $path === null || $path === '' ? '' : '/'.trim($path, '/');
    };

    $buttonPaths = collect([$primaryUrl, $secondaryUrl])
        ->map($normalizePath)
        ->filter()
        ->all();

    $visibleLinks = collect($links)
        ->filter(fn ($link): bool => isset($link['label'], $link['url']))
        ->reject(fn ($link): bool => in_array(
            $normalizePath($link['url']),
            $buttonPaths,
            true,
        ))
        ->unique('label')
        ->take(4)
        ->values();
@endphp

<section
    {{ $attributes->class([
        'error-page',
        'vdbs-error-page',
        'error-page--'.$variant,
        'vdbs-error-page--'.$variant,
        'error-page--compact' => $compact,
        'vdbs-error-page--compact' => $compact,
        'error-page--bild' => $image !== null,
        'vdbs-error-page--bild' => $image !== null,
    ]) }}
    aria-labelledby="{{ $titleId }}"
>
    <div
        class="error-page__visual vdbs-error-page__visual"
        @if ($image === null || $imageAlt === '') aria-hidden="true" @endif
    >
        @if ($image !== null)
            <img
                class="error-page__bild vdbs-error-page__bild"
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                width="1536"
                height="1024"
                decoding="async"
            >
        @endif

        <span class="error-page__signal vdbs-error-page__signal"></span>
        <p class="error-page__code vdbs-error-page__code">
            {{ $code }}
        </p>
    </div>

    <div class="error-page__content vdbs-error-page__content">
        <p class="error-page__eyebrow vdbs-error-page__eyebrow">
            {{ $eyebrow }}
        </p>

        <h1
            class="error-page__title vdbs-error-page__title"
            id="{{ $titleId }}"
        >
            {{ $title }}
        </h1>

        <p class="error-page__description vdbs-error-page__description">
            {{ $description }}
        </p>

        @if ($support !== null)
            <p class="error-page__support vdbs-error-page__support">
                {{ $support }}
            </p>
        @endif

        @if (
            ($primaryLabel !== null && $primaryUrl !== null)
            || ($secondaryLabel !== null && $secondaryUrl !== null)
        )
            <div class="error-page__actions vdbs-error-page__actions">
                @if ($primaryLabel !== null && $primaryUrl !== null)
                    <a class="btn" href="{{ $primaryUrl }}">
                        {{ $primaryLabel }}
                    </a>
                @endif

                @if ($secondaryLabel !== null && $secondaryUrl !== null)
                    <a class="btn btn--secondary" href="{{ $secondaryUrl }}">
                        {{ $secondaryLabel }}
                    </a>
                @endif
            </div>
        @endif

        @if ($visibleLinks->isNotEmpty())
            <nav
                class="error-page__links vdbs-error-page__links"
                aria-labelledby="{{ $titleId }}-links"
            >
                <h2
                    class="error-page__links-title vdbs-error-page__links-title"
                    id="{{ $titleId }}-links"
                >
                    {{ $linksTitle }}
                </h2>

                <ul class="error-page__links-list vdbs-error-page__links-list" role="list">
                    @foreach ($visibleLinks as $link)
                        <li>
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <p class="error-page__technical vdbs-error-page__technical">
            Fehlercode {{ $code }}
        </p>
    </div>
</section>
