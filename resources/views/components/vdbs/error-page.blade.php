@props([
    'code',
    'title',
    'description',
    'support' => null,
    'variant' => 'info',
    'primaryLabel' => null,
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

@php
    $alertVariant = in_array($variant, ['info', 'warning', 'danger'], true) ? ($variant === 'danger' ? 'error' : $variant) : 'info';
@endphp

<section {{ $attributes }} aria-labelledby="error-title">
    <p style="margin-bottom:var(--space-2);color:var(--text-muted);font-weight:var(--font-weight-bold)">Fehler {{ $code }}</p>
    <h1 id="error-title">{{ $title }}</h1>
    <x-vdbs.alert :variant="$alertVariant">{{ $description }}</x-vdbs.alert>
    @if ($support) <p>{{ $support }}</p> @endif

    <div style="display:flex;flex-wrap:wrap;gap:var(--space-3)">
        @if ($primaryLabel && $primaryUrl)
            <x-vdbs.button :href="$primaryUrl">{{ $primaryLabel }}</x-vdbs.button>
        @endif
        @if ($secondaryLabel && $secondaryUrl)
            <x-vdbs.button variant="secondary" :href="$secondaryUrl">{{ $secondaryLabel }}</x-vdbs.button>
        @endif
    </div>
</section>
