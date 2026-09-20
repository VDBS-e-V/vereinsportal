@props([])

<div {{ $attributes->class(['service-start']) }}>
    @isset($hero)
        {{ $hero }}
    @endisset

    @isset($overview)
        {{ $overview }}
    @endisset

    @isset($articles)
        {{ $articles }}
    @endisset

    @isset($access)
        {{ $access }}
    @endisset

    @isset($contact)
        {{ $contact }}
    @endisset

    {{ $slot }}
</div>
