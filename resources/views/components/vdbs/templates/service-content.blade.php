@props([])

<div {{ $attributes->class(['service-information']) }}>
    @isset($intro)
        {{ $intro }}
    @endisset

    @isset($faq)
        {{ $faq }}
    @endisset

    @isset($callout)
        {{ $callout }}
    @endisset

    {{ $slot }}
</div>
