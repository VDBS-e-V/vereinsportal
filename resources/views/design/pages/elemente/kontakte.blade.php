@extends('design.layout')

@section('title', 'Kontakte')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Kontakte</h1>
            <p class="page-title__lead">
                Kontaktblöcke zeigen Zuständigkeit und erreichbare Kontaktwege,
                ohne daraus automatisch profilartige Karten zu machen.
            </p>
        </header>

        <section class="stack">
            <h2>Ansprechpersonen</h2>

            <div class="contact-list">
                <x-vdbs.contact-block
                    name="Erika Muster"
                    role="Mitgliederverwaltung"
                    email="erika.muster@example.test"
                    phone="+49 30 123456-10"
                    address="Beispielstraße 12, 10115 Berlin"
                >
                    <x-slot:actions>
                        <a class="btn btn--secondary btn--sm" href="#">Kontakt aufnehmen</a>
                    </x-slot:actions>
                </x-vdbs.contact-block>

                <x-vdbs.contact-block
                    name="Max Beispiel"
                    role="Portal und Technik"
                    email="max.beispiel@example.test"
                    phone="+49 30 123456-20"
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Die Zuständigkeit steht direkt beim Namen.</li>
                <li>E-Mail-Adresse und Telefonnummer bleiben sichtbar und direkt nutzbar.</li>
                <li>Postalische Anschriften werden semantisch als <code>address</code> ausgegeben.</li>
                <li>Profilbilder sind optional und keine Voraussetzung für einen Kontaktblock.</li>
            </ul>
        </section>
    </div>
@endsection
