@props([
    'label' => 'Wird geladen',
    'block' => false,
    'live' => false,
])

<div
    {{ $attributes->class([
        'loading-state',
        'vdbs-loading-state',
        'loading-state--block' => $block,
        'vdbs-loading-state--block' => $block,
    ]) }}
    aria-busy="true"
    @if ($live)
        role="status"
        aria-live="polite"
    @endif
>
    <span
        class="loading-state__spinner vdbs-loading-state__spinner"
        aria-hidden="true"
    ></span>
    <span class="loading-state__label vdbs-loading-state__label">
        {{ $label }}
    </span>
</div>
