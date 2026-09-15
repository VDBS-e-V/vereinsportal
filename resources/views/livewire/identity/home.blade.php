<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')]
    class extends Component {};

?>

@php
    $accountUrl = auth()->check() ? route('my.account') : route('my.login');

    $portalAreas = [
        ['label' => 'Vorstand', 'icon' => 'home'],
        ['label' => 'Verwaltung', 'icon' => 'settings'],
        ['label' => 'Development', 'icon' => 'file'],
        ['label' => 'Teamende', 'icon' => 'user'],
        ['label' => 'Schule', 'icon' => 'file'],
        ['label' => 'Bibliocollect', 'icon' => 'file'],
        ['label' => 'Webmail', 'icon' => 'mail'],
        ['label' => 'Moodle', 'icon' => 'file'],
        ['label' => 'Nextcloud', 'icon' => 'upload'],
        ['label' => 'MethodenMatrix', 'icon' => 'file'],
    ];

    $articleCards = [
        [
            'title' => 'Mehr sicherheit',
            'image' => 'images/portal/article-security.jpg',
            'alt' => 'Nahaufnahme eines Bildschirms mit dem Schriftzug Security',
            'description' => 'Günter M. Ziegler, Professor für Mathematik, ist zum Präsidenten der Freien Universität Berlin wiedergewählt worden. In der Sitzung des erweiterten...',
        ],
        [
            'title' => 'Digitalisierte Verwaltung',
            'image' => 'images/portal/article-admin.jpg',
            'alt' => 'Arbeitsplätze mit Computern und Laptops',
            'description' => 'Wir sind stolz, dass wir es innerhalb kurzer Zeit geschafft haben, einen Großteil unserer Verwaltung digital zu gestalten. Hierbei setzten wir auf...',
        ],
        [
            'title' => 'Eigene Cloud',
            'image' => 'images/portal/article-cloud.jpg',
            'alt' => 'Cloud-Symbol vor einer Leiterplattenstruktur',
            'description' => 'Eine eigene Cloud war von Beginn an für uns ein wichtiges Ziel.',
        ],
    ];
@endphp

<x-vdbs.templates.service-start>
    <x-slot:hero>
        <section class="service-hero" aria-labelledby="service-hero-title">
            <div class="service-hero__media">
                <img
                    class="service-hero__image"
                    src="{{ asset('images/portal/portal-hero.jpg') }}"
                    alt="Heller Bibliotheksraum mit Bücherregalen"
                >
                <h1 id="service-hero-title" class="service-hero__title">
                    Willkommen im VDBS Serviceportal
                </h1>
            </div>

            <nav class="service-hero__tiles" aria-label="Schnelleinstiege">
                <a class="service-hero__tile service-hero__tile--about" href="{{ route('portal.about') }}">
                    Über das Portal
                </a>
                <a class="service-hero__tile service-hero__tile--access" href="{{ route('portal.access') }}">
                    Zugang zum Portal
                </a>
                <a class="service-hero__tile service-hero__tile--account" href="{{ $accountUrl }}">
                    Mein Konto
                </a>
                <a class="service-hero__tile service-hero__tile--help" href="{{ route('portal.faq') }}">
                    Hilfe
                </a>
            </nav>
        </section>
    </x-slot:hero>

    <x-slot:overview>
        <section class="service-start__intro" aria-labelledby="portal-overview-heading">
            <h2 id="portal-overview-heading">Das VDBS Serviceportal</h2>
            <p>
                Das VDBS Serviceportal ist Ihre zentrale Anlaufstelle für alle Informationen und
                Dienstleistungen rund um den VDBS. Hier finden Sie alle Bereiche des VDBS Serviceportals.
            </p>

            <div class="service-area-links" aria-label="Bereiche des VDBS Serviceportals">
                @foreach ($portalAreas as $area)
                    <span class="service-area-links__item">
                        <x-vdbs.icon :name="$area['icon']" size="16" />
                        <span>{{ $area['label'] }}</span>
                    </span>
                @endforeach
            </div>
        </section>
    </x-slot:overview>

    <x-slot:articles>
        <section class="service-start__articles" aria-labelledby="recommended-articles-heading">
            <div class="service-start__section-heading">
                <h2 id="recommended-articles-heading">Empfohlene Artikel</h2>
                <p>Schaue dir unsere aktuellen Themenartikel an</p>
            </div>

            <div class="service-article-grid">
                @foreach ($articleCards as $article)
                    <article class="service-article-card">
                        <img
                            class="service-article-card__image"
                            src="{{ asset($article['image']) }}"
                            alt="{{ $article['alt'] }}"
                        >
                        <div class="service-article-card__body">
                            <h3>{{ $article['title'] }}</h3>
                            <p>{{ $article['description'] }}</p>
                        </div>
                        <div class="service-article-card__footer">
                            <span>Themenartikel</span>
                            <span class="service-article-card__arrow" aria-hidden="true">→</span>
                        </div>
                    </article>
                @endforeach
            </div>

            <a class="service-start__all-articles" href="{{ route('portal.faq') }}">
                <span aria-hidden="true">☷</span>
                <span>Alle Artikel anzeigen</span>
            </a>
        </section>
    </x-slot:articles>

    <x-slot:access>
        <x-vdbs.content-split
            variant="image"
            side="right"
            layout="visual-dominant"
            tone="transparent"
            :image="asset('images/portal/portal-access.jpg')"
            alt="Steinerner Torbogen"
            caption="Steinerner Torbogen als Symbol für den Zugang zum VDBS Serviceportal"
            source="Projektbestand VDBS Serviceportal"
            aria-labelledby="portal-access-heading"
        >
            <x-slot:heading>
                <h2 id="portal-access-heading">Zugang zum Portal</h2>
            </x-slot:heading>

            <p>
                Hier finden Sie alle Informationen, die Sie benötigen, um auf das VDBS Serviceportal
                zuzugreifen. Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Wenn Sie
                noch kein Konto haben, können Sie sich hier registrieren.
            </p>

            <x-slot:actions>
                <a class="btn" href="{{ route('portal.access') }}">
                    <x-vdbs.icon name="arrow-right" size="18" />
                    <span>Zugang zum Portal</span>
                </a>
                <a class="btn btn--secondary" href="{{ route('portal.contact') }}">
                    <x-vdbs.icon name="help" size="18" />
                    <span>Hilfe kontaktieren</span>
                </a>
            </x-slot:actions>
        </x-vdbs.content-split>
    </x-slot:access>

    <x-slot:contact>
        <x-vdbs.content-split
            variant="image"
            side="left"
            layout="balanced"
            tone="subtle"
            width="full"
            :image="asset('images/portal/portal-contact.jpg')"
            alt="Leuchtendes Briefumschlag-Symbol"
            caption="Leuchtendes Briefumschlag-Symbol für Kontakt und Support"
            source="Projektbestand VDBS Serviceportal"
            aria-labelledby="portal-contact-heading"
        >
            <x-slot:heading>
                <h2 id="portal-contact-heading">Kontakt</h2>
            </x-slot:heading>

            <p>
                Sollten Sie Fragen oder Probleme haben, können Sie uns jederzeit kontaktieren. Wir helfen
                Ihnen gerne weiter. Sie erreichen uns unter den E-Mail-Adressen:
            </p>
            <p>
                <strong>Support:</strong>
                <a href="mailto:support@portal.vdb.schule">support@portal.vdb.schule</a><br>
                <strong>Kontakt:</strong>
                <a href="mailto:kontakt@vdb.schule">kontakt@vdb.schule</a>
            </p>

            <x-slot:actions>
                <a class="btn" href="{{ route('portal.contact') }}">
                    <x-vdbs.icon name="arrow-right" size="18" />
                    <span>Zum Kontaktformular</span>
                </a>
            </x-slot:actions>
        </x-vdbs.content-split>
    </x-slot:contact>
</x-vdbs.templates.service-start>
