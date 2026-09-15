@extends('design.layout')

@section('title', 'Service-Portal · Formularseiten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Service-Portal</p>
            <h1 class="page-title__title">Formularseiten</h1>
            <p class="page-title__lead">
                Graue Formularfläche nach den Referenzscreens. Die Vorlage unterstützt sowohl einen Seitentitel
                oberhalb des Panels als auch einen zentrierten Titel innerhalb des Panels.
            </p>
        </header>

        <section class="stack stack--lg">
            <h2>Zugangsantrag / Prozess</h2>

            <div class="design-example service-template-preview">
                <x-vdbs.templates.service-panel
                    title="Antrag - Zugang zum Portal"
                    title-id="template-access-heading"
                    page-class="access-application"
                >
                    <nav class="application-stepper" aria-label="Beispielschritte">
                        <span>0. Einwilligung</span>
                        <span aria-hidden="true">›</span>
                        <span class="application-stepper__active">1. Verbindung</span>
                        <span aria-hidden="true">›</span>
                        <span>2. Kontaktdaten</span>
                        <span aria-hidden="true">›</span>
                        <span>3. ToS &amp; Prüfen</span>
                    </nav>

                    <form class="mockup-form access-application__form">
                        <div class="form__field">
                            <label class="form__label" for="template-connection">Verbindung zum Verein *</label>
                            <select class="form__control" id="template-connection">
                                <option>Teamer:in</option>
                            </select>
                        </div>
                        <div class="form__field">
                            <label class="form__label" for="template-school">Träger / Schule (optional)</label>
                            <input class="form__control" id="template-school" type="text" placeholder="Träger oder Schule">
                        </div>
                        <div class="form__field">
                            <label class="form__label" for="template-details">Weitere Details (optional)</label>
                            <textarea class="form__control mockup-form__textarea" id="template-details" rows="4" placeholder="z.B. Rolle, Verein, Zugehörigkeit"></textarea>
                        </div>
                        <div class="mockup-form__actions">
                            <button class="btn" type="button">Zurück</button>
                            <button class="btn" type="button">Weiter</button>
                        </div>
                    </form>
                </x-vdbs.templates.service-panel>
            </div>
        </section>

        <section class="section stack stack--lg">
            <h2>Kontaktformular</h2>

            <div class="design-example service-template-preview">
                <x-vdbs.templates.service-panel
                    title="Kontaktformular"
                    title-id="template-contact-heading"
                    title-position="inside"
                    page-class="contact-form-page"
                    panel-class="contact-form-panel"
                >
                    <form class="mockup-form contact-form-panel__form">
                        <div class="mockup-form__grid">
                            <div class="form__field">
                                <label class="form__label" for="template-first-name">Vorname</label>
                                <input class="form__control" id="template-first-name" type="text" placeholder="Max">
                            </div>
                            <div class="form__field">
                                <label class="form__label" for="template-last-name">Nachname</label>
                                <input class="form__control" id="template-last-name" type="text" placeholder="Mustermann">
                            </div>
                        </div>
                        <div class="form__field">
                            <label class="form__label" for="template-email">E-Mail-Adresse</label>
                            <input class="form__control" id="template-email" type="email" placeholder="max.mustermann@beispiel.xyz">
                        </div>
                        <div class="form__field">
                            <label class="form__label" for="template-message">Inhalt</label>
                            <textarea class="form__control mockup-form__textarea" id="template-message" rows="5" placeholder="Ihre Nachricht..."></textarea>
                        </div>
                        <div class="mockup-form__actions">
                            <button class="btn" type="button">Absenden</button>
                        </div>
                    </form>
                </x-vdbs.templates.service-panel>
            </div>
        </section>

        <section class="section stack service-template-note">
            <h2>Strukturregeln</h2>
            <ul>
                <li>Formularseiten liegen auf einer klar abgegrenzten hellgrauen Fläche.</li>
                <li>Prozessseiten führen die Schritte oberhalb der Felder auf einer Linie auf.</li>
                <li>Kontaktformulare können den Titel innerhalb des Panels zentrieren.</li>
                <li>Felder verwenden sichtbare Labels; Placeholder ergänzen nur Beispiele.</li>
            </ul>
        </section>
    </div>
@endsection
