@extends('design.layout')

@section('title', 'Service-Portal · Startseite')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Service-Portal</p>
            <h1 class="page-title__title">Startseite</h1>
            <p class="page-title__lead">
                Aufbau der Startseite nach der Referenz aus Issue #50: Hero mit vier Schnelleinstiegen,
                Portalübersicht, drei Themenartikel sowie zwei große Service-Teaser.
            </p>
        </header>

        <div class="design-example service-template-preview">
            <x-vdbs.templates.service-start>
                <x-slot:hero>
                    <section class="service-hero" aria-labelledby="template-service-start-title">
                        <div class="service-hero__media">
                            <img
                                class="service-hero__image"
                                src="{{ asset('images/portal/portal-hero.jpg') }}"
                                alt="Heller Bibliotheksraum mit Bücherregalen"
                            >
                            <h2 id="template-service-start-title" class="service-hero__title">
                                Willkommen im VDBS Serviceportal
                            </h2>
                        </div>

                        <nav class="service-hero__tiles" aria-label="Beispiel-Schnelleinstiege">
                            <a class="service-hero__tile service-hero__tile--about" href="#">Über das Portal</a>
                            <a class="service-hero__tile service-hero__tile--access" href="#">Zugang zum Portal</a>
                            <a class="service-hero__tile service-hero__tile--account" href="#">Mein Konto</a>
                            <a class="service-hero__tile service-hero__tile--help" href="#">Hilfe</a>
                        </nav>
                    </section>
                </x-slot:hero>

                <x-slot:overview>
                    <section class="service-start__intro">
                        <h2>Das VDBS Serviceportal</h2>
                        <p>
                            Die zentrale Anlaufstelle für Informationen und Dienstleistungen rund um den VDBS.
                        </p>
                        <div class="service-area-links" aria-label="Beispielbereiche">
                            @foreach (['Vorstand', 'Verwaltung', 'Development', 'Teamende', 'Schule', 'Bibliocollect', 'Webmail', 'Moodle', 'Nextcloud', 'MethodenMatrix'] as $area)
                                <span class="service-area-links__item">{{ $area }}</span>
                            @endforeach
                        </div>
                    </section>
                </x-slot:overview>

                <x-slot:articles>
                    <section class="service-start__articles">
                        <div class="service-start__section-heading">
                            <h2>Empfohlene Artikel</h2>
                            <p>Schaue dir unsere aktuellen Themenartikel an</p>
                        </div>

                        <div class="service-article-grid">
                            @foreach ([
                                ['Mehr sicherheit', 'images/portal/article-security.jpg'],
                                ['Digitalisierte Verwaltung', 'images/portal/article-admin.jpg'],
                                ['Eigene Cloud', 'images/portal/article-cloud.jpg'],
                            ] as [$title, $image])
                                <article class="service-article-card">
                                    <img class="service-article-card__image" src="{{ asset($image) }}" alt="">
                                    <div class="service-article-card__body">
                                        <h3>{{ $title }}</h3>
                                        <p>Kurzer Vorschautext für einen Themenartikel im Service-Portal.</p>
                                    </div>
                                    <div class="service-article-card__footer">
                                        <span>Themenartikel</span>
                                        <span class="service-article-card__arrow" aria-hidden="true">→</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </x-slot:articles>

                <x-slot:access>
                    <section class="service-promo service-promo--access">
                        <div class="service-promo__copy">
                            <h2>Zugang zum Portal</h2>
                            <p>Informationen zu Anmeldung, Registrierung und Zugang zum Service-Portal.</p>
                            <a class="btn" href="#">Zugang zum Portal</a>
                        </div>
                        <img class="service-promo__image" src="{{ asset('images/portal/portal-access.jpg') }}" alt="Steinerner Torbogen">
                    </section>
                </x-slot:access>

                <x-slot:contact>
                    <section class="service-promo service-promo--contact">
                        <img class="service-promo__image" src="{{ asset('images/portal/portal-contact.jpg') }}" alt="Leuchtendes Briefumschlag-Symbol">
                        <div class="service-promo__copy">
                            <h2>Kontakt</h2>
                            <p>Direkter Einstieg in Hilfe und Kontakt.</p>
                            <a class="btn" href="#">Zum Kontaktformular</a>
                        </div>
                    </section>
                </x-slot:contact>
            </x-vdbs.templates.service-start>
        </div>

        <section class="section stack service-template-note">
            <h2>Strukturregeln</h2>
            <ul>
                <li>Der Hero steht direkt am Seitenanfang und enthält genau vier gleichwertige Schnelleinstiege.</li>
                <li>Die Bereiche des Portals erscheinen als kompakte Chips unter der Einleitung.</li>
                <li>Empfohlene Artikel werden auf Desktop dreispaltig und auf kleinen Viewports einspaltig dargestellt.</li>
                <li>Zugang und Kontakt bilden den Abschluss als großflächige Bild/Text-Teaser.</li>
            </ul>
        </section>

        <section class="section stack service-template-note">
            <h2>Barrierefreiheit</h2>
            <p>
                Die Startseite benötigt genau eine Hauptüberschrift. Bilder erhalten sinnvolle Alternativtexte,
                wenn sie Information tragen. Schnelleinstiege und Call-to-Actions müssen als echte Links oder
                Buttons umgesetzt werden und eine sichtbare Fokusdarstellung behalten.
            </p>
        </section>
    </div>
@endsection
