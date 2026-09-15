@extends('design.layout')

@section('title', 'Content Split')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Muster</p>
            <h1 class="page-title__title">Content Split</h1>
            <p class="page-title__lead">
                Zweispaltiges Inhaltsmuster mit Hauptinhalt und Bild oder Button-Liste.
                Auf kleinen Viewports steht der Inhalt immer vor dem sekundären Bereich.
            </p>
        </header>

        <section class="stack stack--lg">
            <h2>Bild · 30/70 · rechts · transparent</h2>

            <x-vdbs.content-split
                variant="image"
                side="right"
                layout="visual-dominant"
                tone="transparent"
                :image="asset('images/portal/portal-access.jpg')"
                alt="Studierende in einer Lehrveranstaltung"
                caption="Studierende in einer Lehrveranstaltung"
                source="VDBS"
            >
                <x-slot:heading>
                    <h2>Studium</h2>
                </x-slot:heading>

                <p>
                    Hier steht ein redaktioneller Einstiegstext. Die Textspalte bleibt
                    bewusst kompakt, während das Bild visuell stärker gewichtet wird.
                </p>

                <x-slot:actions>
                    <a class="btn" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Studienangebot entdecken</span>
                    </a>
                    <a class="btn btn--secondary" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Mehr erfahren</span>
                    </a>
                </x-slot:actions>
            </x-vdbs.content-split>
        </section>

        <section class="stack stack--lg">
            <h2>Bild · 50/50 · links · Hintergrund</h2>

            <x-vdbs.content-split
                variant="image"
                side="left"
                layout="balanced"
                tone="subtle"
                :image="asset('images/portal/portal-contact.jpg')"
                alt="Leuchtendes Briefumschlag-Symbol"
                caption="Kontakt und Unterstützung im VDBS Serviceportal"
                source="VDBS"
            >
                <x-slot:heading>
                    <h2>Kontakt</h2>
                </x-slot:heading>

                <p>
                    Sollten Sie Fragen oder Probleme haben, können Sie uns jederzeit
                    kontaktieren. Wir helfen Ihnen gerne weiter.
                </p>

                <x-slot:actions>
                    <a class="btn" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Zum Kontaktformular</span>
                    </a>
                </x-slot:actions>
            </x-vdbs.content-split>
        </section>

        <section class="stack stack--lg">
            <h2>Button-Liste · 70/30 · rechts · Hintergrund</h2>

            <x-vdbs.content-split
                variant="actions"
                side="right"
                layout="actions-compact"
                tone="subtle"
                action-list-label="Themen zu Haltung und Verantwortung"
            >
                <x-slot:heading>
                    <h2>Haltung &amp; Verantwortung</h2>
                </x-slot:heading>

                <p>
                    Dieser Bereich eignet sich für einen kurzen einordnenden Text mit
                    zwei bis drei klar priorisierten weiterführenden Zielen.
                </p>

                <x-slot:actionList>
                    <a class="btn" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Die politische Universität</span>
                    </a>
                    <a class="btn btn--secondary" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Maßnahmen gegen Diskriminierung</span>
                    </a>
                    <a class="btn btn--secondary" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Weitere Informationen</span>
                    </a>
                </x-slot:actionList>
            </x-vdbs.content-split>
        </section>

        <section class="stack stack--lg">
            <h2>Button-Liste · 60/40 · links · transparent</h2>

            <x-vdbs.content-split
                variant="actions"
                side="left"
                layout="actions-wide"
                tone="transparent"
                action-list-label="Weiterführende Angebote"
            >
                <x-slot:heading>
                    <h2>Weitere Angebote</h2>
                </x-slot:heading>

                <p>
                    Die breitere Aktionsspalte eignet sich für längere Linktexte,
                    ohne dass daraus eine beliebige Navigationssammlung wird.
                </p>

                <x-slot:actionList>
                    <a class="btn" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Erstes weiterführendes Angebot</span>
                    </a>
                    <a class="btn btn--secondary" href="#">
                        <x-vdbs.icon name="arrow-right" size="18" />
                        <span>Zweites weiterführendes Angebot</span>
                    </a>
                </x-slot:actionList>
            </x-vdbs.content-split>
        </section>
    </div>
@endsection
