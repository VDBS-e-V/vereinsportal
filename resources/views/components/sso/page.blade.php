@props(['title', 'heading', 'lead' => null])

{{--
    Seitengerüst der Anmeldung als Identity Provider (Single Sign-On): Band „Single Sign-On“, links das Logo, in der Mitte die Überschrift mit Linie und der Inhalt,
    rechts auf Wunsch eine graue Box (Slot aside, z. B. „Beschreibung des Dienstes“). Auf dem Handy stehen die Spalten untereinander.
    title    Seitentitel (Browser-Tab)
    heading  Überschrift im Inhalt (h1)
    lead     kurzer Text unter der Überschrift (freiwillig)
    Slots:   Standard (Inhalt), aside (graue Box rechts)
--}}

<x-layouts.app :title="$title" width="full">
    <section class="sso-band" aria-label="Single Sign-On">
        <x-vdbs.layout.container><p class="sso-band__label">Single Sign-On</p></x-vdbs.layout.container>
    </section>

    <x-vdbs.layout.container>
        <div @class(['sso-page', 'sso-page--aside' => isset($aside)])>
            <div class="sso-page__brand" aria-hidden="true">
                <img src="{{ asset('images/brand/vdbs-bildmarke-breit.png') }}" alt="" width="240">
            </div>

            <div class="sso-page__main">
                <h1 class="sso-page__heading">{{ $heading }}</h1>
                @if ($lead)<p class="sso-page__lead">{{ $lead }}</p>@endif
                {{ $slot }}
            </div>

            @isset($aside)
                <aside class="sso-page__aside">{{ $aside }}</aside>
            @endisset
        </div>
    </x-vdbs.layout.container>
</x-layouts.app>
