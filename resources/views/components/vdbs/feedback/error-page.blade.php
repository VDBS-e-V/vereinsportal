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

{{--
    Fehlerseite (Paket "feedback"): Fehlernummer, Titel, Erklärung und Wege zurück.
    variant  info | warning | danger   Farbe des Akzents (Balken und große Nummer)
    Die große Nummer ist Zierde (aria-hidden); "Fehler 404" steht als Text über dem Titel.
--}}

@php
    $variant = in_array($variant, ['info', 'warning', 'danger'], true) ? $variant : 'info';
@endphp

<section {{ $attributes->class(['vdbs-error-page', "vdbs-error-page--{$variant}"]) }} aria-labelledby="error-title">
    <div class="vdbs-error-page__text">
        <p class="vdbs-error-page__eyebrow">Fehler {{ $code }}</p>
        <h1 class="vdbs-error-page__title" id="error-title">{{ $title }}</h1>
        <p class="vdbs-error-page__lead">{{ $description }}</p>
        @if ($support) <p class="vdbs-error-page__support">{{ $support }}</p> @endif

        <div class="vdbs-error-page__actions">
            @if ($primaryLabel && $primaryUrl)
                <x-vdbs.actions.button :href="$primaryUrl" icon="house">{{ $primaryLabel }}</x-vdbs.actions.button>
            @endif
            @if ($secondaryLabel && $secondaryUrl)
                <x-vdbs.actions.button variant="secondary" :href="$secondaryUrl">{{ $secondaryLabel }}</x-vdbs.actions.button>
            @endif
        </div>
    </div>

    <p class="vdbs-error-page__code" aria-hidden="true">{{ $code }}</p>
</section>
