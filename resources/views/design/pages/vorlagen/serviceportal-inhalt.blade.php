@extends('design.layout')

@section('title', 'Service-Portal · Inhaltsseite')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Service-Portal</p>
            <h1 class="page-title__title">Inhalts- und FAQ-Seite</h1>
            <p class="page-title__lead">
                Breite redaktionelle Inhaltsseite nach den Referenzscreens: mehrere Textabschnitte,
                FAQ-Disclosure und ein großer Zugangs-Teaser als Abschluss.
            </p>
        </header>

        <div class="design-example service-template-preview">
            <x-vdbs.templates.service-content>
                <x-slot:intro>
                    <section class="service-information__intro">
                        <h2>Unser Service-Portal</h2>

                        <div class="service-information__block">
                            <h3>Smart. Vernetzt. Engagiert.</h3>
                            <p>
                                Das Portal bündelt Informationen und Werkzeuge für Teamer:innen,
                                Vereinsmitglieder, Verwaltung und Schulen.
                            </p>
                        </div>

                        <div class="service-information__block">
                            <h3>Was erwartet Sie im Portal?</h3>
                            <p>
                                Inhalte werden in klaren Abschnitten ohne Kartenraster präsentiert.
                                Zwischenüberschriften strukturieren längere Texte.
                            </p>
                        </div>

                        <div class="service-information__block">
                            <h3>Ihre Vorteile auf einen Blick</h3>
                            <p>Zentraler Informationszugang • Effiziente Kommunikation • Transparenz</p>
                        </div>
                    </section>
                </x-slot:intro>

                <x-slot:faq>
                    <section class="service-faq">
                        <h2>Häufig gestellte Fragen</h2>
                        <p class="service-faq__intro">
                            Die FAQ folgt direkt auf den redaktionellen Teil und bleibt im selben Inhaltsraster.
                        </p>
                        <div class="service-faq__items">
                            <details open>
                                <summary>Was ist das Service-Portal und wofür wurde es entwickelt?</summary>
                                <div class="service-faq__answer">
                                    <p>Es ist die zentrale Online-Anlaufstelle für Informationen und Zusammenarbeit.</p>
                                </div>
                            </details>
                            <details>
                                <summary>Wer kann das Service-Portal nutzen?</summary>
                                <div class="service-faq__answer">
                                    <p>Alle Zielgruppen, die mit dem Verein und seinen Angeboten verbunden sind.</p>
                                </div>
                            </details>
                            <details>
                                <summary>Wo finde ich Hilfe?</summary>
                                <div class="service-faq__answer">
                                    <p>Über den Support und das Kontaktformular des Portals.</p>
                                </div>
                            </details>
                        </div>
                    </section>
                </x-slot:faq>

                <x-slot:callout>
                    <section class="service-promo service-promo--access service-information__access">
                        <div class="service-promo__copy">
                            <h2>Zugang zum Portal</h2>
                            <p>Der Zugangs-Teaser schließt die Inhaltsseite visuell und inhaltlich ab.</p>
                            <a class="btn" href="#">Zugang zum Portal</a>
                        </div>
                        <img class="service-promo__image" src="{{ asset('images/portal/portal-access.jpg') }}" alt="Steinerner Torbogen">
                    </section>
                </x-slot:callout>
            </x-vdbs.templates.service-content>
        </div>

        <section class="section stack service-template-note">
            <h2>Strukturregeln</h2>
            <ul>
                <li>Redaktionelle Inhalte bleiben in einem ruhigen, breiten Lesebereich ohne zusätzliche Karten.</li>
                <li>FAQ-Einträge werden als native Disclosure-Elemente aufgebaut.</li>
                <li>Der Zugangs-Teaser steht nach Inhalt und FAQ und bildet den klaren nächsten Schritt.</li>
            </ul>
        </section>
    </div>
@endsection
