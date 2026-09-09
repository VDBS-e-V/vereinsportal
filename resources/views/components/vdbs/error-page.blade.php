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
@endphp

<section
    {{ $attributes->class([
        'error-page',
        'vdbs-error-page',
        'error-page--'.$variant,
        'vdbs-error-page--'.$variant,
        'error-page--compact' => $compact,
        'vdbs-error-page--compact' => $compact,
    ]) }}
    aria-labelledby="{{ $titleId }}"
>
    <div class="error-page__visual vdbs-error-page__visual" aria-hidden="true">
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

        <p class="error-page__technical vdbs-error-page__technical">
            Fehlercode {{ $code }}
        </p>
    </div>
</section>
