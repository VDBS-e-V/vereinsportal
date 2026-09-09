@props([
    'title',
    'description' => null,
    'headingLevel' => 3,
    'compact' => false,
])

@php
    $headingLevel = in_array((int) $headingLevel, [2, 3, 4], true)
        ? (int) $headingLevel
        : 3;
@endphp

<section
    {{ $attributes->class([
        'empty-state',
        'vdbs-empty-state',
        'empty-state--compact' => $compact,
        'vdbs-empty-state--compact' => $compact,
    ]) }}
>
    <div class="empty-state__content vdbs-empty-state__content">
        @if ($headingLevel === 2)
            <h2 class="empty-state__title vdbs-empty-state__title">{{ $title }}</h2>
        @elseif ($headingLevel === 4)
            <h4 class="empty-state__title vdbs-empty-state__title">{{ $title }}</h4>
        @else
            <h3 class="empty-state__title vdbs-empty-state__title">{{ $title }}</h3>
        @endif

        @if ($description !== null)
            <p class="empty-state__description vdbs-empty-state__description">
                {{ $description }}
            </p>
        @endif

        @if (trim((string) $slot) !== '')
            <div class="empty-state__body vdbs-empty-state__body">
                {{ $slot }}
            </div>
        @endif
    </div>

    @isset($actions)
        <div class="empty-state__actions vdbs-empty-state__actions">
            {{ $actions }}
        </div>
    @endisset
</section>
