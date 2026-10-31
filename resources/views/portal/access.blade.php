@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Zugang zum Portal']];
    $steps = ['Einwilligung', 'Verbindung', 'Kontaktdaten', 'Prüfen und absenden'];
    $legal = config('portal.legal');
@endphp

<x-layouts.app title="Zugang zum Portal" width="medium">
    <x-vdbs.layout.page-header title="Zugang zum Portal" lead="So melden Sie sich an oder beantragen Ihren Zugang." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.content.prose>
        <h2>Sie haben bereits ein Konto</h2>
        <p>Melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort an. Haben Sie eine Zwei-Faktor-Anmeldung eingerichtet, geben Sie danach den Code ein.</p>
    </x-vdbs.content.prose>

    <p style="margin-top:var(--space-4)">
        @auth
            <x-vdbs.actions.button :href="route('account.profile')" icon="user">Zu Ihrem Konto</x-vdbs.actions.button>
        @else
            <x-vdbs.actions.button :href="route('login')" icon="log-in">Zur Anmeldung</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('password.request')">Passwort vergessen</x-vdbs.actions.button>
        @endauth
    </p>

    <x-vdbs.content.prose style="margin-top:var(--space-8)">
        <h2>Sie haben noch kein Konto</h2>
        <p>Teamer:innen, Schüler:innen, Lehrkräfte und Partner:innen beantragen den Zugang hier. Die Geschäftsstelle prüft Ihren Antrag und legt bei Zustimmung Ihr Konto an; Sie erhalten dann eine E-Mail mit einem Link, über den Sie Ihr Passwort festlegen.</p>
        <p><strong>Vereinsmitglieder</strong> wenden sich bitte an den Vorstand, um Zugang zu erhalten (<a href="{{ route('portal.contact') }}">Kontaktformular</a>, Empfänger „Vorstand“). Wer Mitglied werden möchte, kann die <a href="{{ route('join.apply') }}">Mitgliedschaft online beantragen</a>.</p>
    </x-vdbs.content.prose>

    @guest
        <section aria-labelledby="request-heading" style="margin-top:var(--space-8)">
            <h2 id="request-heading" style="font-size:var(--font-size-h3)">Antrag auf Zugang</h2>

            <x-vdbs.forms.form :action="route('portal.access.store')" :files="true" :summary="false" data-vdbs-wizard>
                <x-vdbs.forms.bot-guard />
                {{-- Schritt 1: Einwilligung --}}
                <div data-vdbs-step data-vdbs-step-title="Einwilligung">
                    <x-vdbs.navigation.stepper class="vdbs-js-block" :steps="$steps" :current="1" label="Schritte des Antrags" />
                    <x-vdbs.forms.form-section title="0. Einwilligung">
                        <x-vdbs.forms.checkbox name="consent" :required="true" label="Ich stimme zu, dass meine Daten zum Zweck der Bearbeitung gespeichert und verarbeitet werden." data-vdbs-review-label="Einwilligung" />
                    </x-vdbs.forms.form-section>
                    <div class="vdbs-btn-group"><button type="button" class="vdbs-btn vdbs-btn--primary vdbs-btn--shape-solid vdbs-btn--size-m vdbs-js-only" data-vdbs-step-next>Weiter</button></div>
                </div>

                {{-- Schritt 2: Verbindung zum Verein und Nachweise --}}
                <div data-vdbs-step data-vdbs-step-title="Verbindung">
                    <x-vdbs.navigation.stepper class="vdbs-js-block" :steps="$steps" :current="2" label="Schritte des Antrags" />
                    <x-vdbs.forms.form-section title="1. Verbindung zum Verein">
                        <x-vdbs.forms.select name="connection" label="Verbindung zum Verein" placeholder="-- Bitte eine Option wählen --" :required="true" :options="$connections" hint="Vereinsmitglieder wenden sich bitte an den Vorstand." data-vdbs-review-label="Verbindung" />
                        <x-vdbs.forms.input name="organization" label="Träger / Schule" :optional="true" maxlength="200" placeholder="Träger oder Schule" data-vdbs-review-label="Träger / Schule" />
                        <x-vdbs.forms.textarea name="details" label="Weitere Details" :optional="true" maxlength="1000" :rows="3" placeholder="z. B. Rolle, Verein, Zugehörigkeit" data-vdbs-review-label="Weitere Details" />
                        <x-vdbs.forms.file name="proofs[]" id="field-proofs" label="Nachweise" :optional="true" :multiple="true" accept=".pdf,.jpg,.jpeg,.png" hint="PDF oder Bilder, höchstens 5 Dateien mit je 5 MB. Die Nachweise werden nach der Entscheidung gelöscht." />
                    </x-vdbs.forms.form-section>
                    <div class="vdbs-btn-group">
                        <button type="button" class="vdbs-btn vdbs-btn--secondary vdbs-btn--shape-outline vdbs-btn--size-m vdbs-js-only" data-vdbs-step-prev>Zurück</button>
                        <button type="button" class="vdbs-btn vdbs-btn--primary vdbs-btn--shape-solid vdbs-btn--size-m vdbs-js-only" data-vdbs-step-next>Weiter</button>
                    </div>
                </div>

                {{-- Schritt 3: Kontaktdaten --}}
                <div data-vdbs-step data-vdbs-step-title="Kontaktdaten">
                    <x-vdbs.navigation.stepper class="vdbs-js-block" :steps="$steps" :current="3" label="Schritte des Antrags" />
                    <x-vdbs.forms.form-section title="2. Kontaktdaten">
                        <x-vdbs.forms.form-grid :columns="2">
                            <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" placeholder="Max" autocomplete="given-name" data-vdbs-review-label="Vorname" />
                            <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" placeholder="Mustermann" autocomplete="family-name" data-vdbs-review-label="Nachname" />
                            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" placeholder="max.mustermann@beispiel.example" autocomplete="email" hint="Dorthin senden wir Ihre Einladung." data-vdbs-review-label="E-Mail-Adresse" />
                            <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" autocomplete="bday" data-vdbs-review-label="Geburtsdatum" />
                            <x-vdbs.forms.input name="phone" label="Telefon" :optional="true" maxlength="50" placeholder="Telefonnummer" autocomplete="tel" data-vdbs-review-label="Telefon" />
                        </x-vdbs.forms.form-grid>
                    </x-vdbs.forms.form-section>
                    <div class="vdbs-btn-group">
                        <button type="button" class="vdbs-btn vdbs-btn--secondary vdbs-btn--shape-outline vdbs-btn--size-m vdbs-js-only" data-vdbs-step-prev>Zurück</button>
                        <button type="button" class="vdbs-btn vdbs-btn--primary vdbs-btn--shape-solid vdbs-btn--size-m vdbs-js-only" data-vdbs-step-next>Daten prüfen</button>
                    </div>
                </div>

                {{-- Schritt 4: Nutzungsbedingungen und Prüfen --}}
                <div data-vdbs-step data-vdbs-step-title="Prüfen und absenden">
                    <x-vdbs.navigation.stepper class="vdbs-js-block" :steps="$steps" :current="4" label="Schritte des Antrags" />
                    <x-vdbs.forms.form-section title="3. Nutzungsbedingungen und Prüfen">
                        <x-vdbs.forms.checkbox name="terms" :required="true" label="Ich akzeptiere die Nutzungsbedingungen und die Datenschutzerklärung." />
                        <p>Die Texte finden Sie hier: <a href="{{ $legal['terms_url'] }}">Nutzungsbedingungen</a>, <a href="{{ $legal['privacy_url'] }}">Datenschutzerklärung</a>.</p>

                        <div class="vdbs-js-block" data-vdbs-review>
                            <h3 style="font-size:var(--font-size-h4)">Prüfen Sie Ihre Angaben</h3>
                            <dl data-vdbs-review-list></dl>
                        </div>
                    </x-vdbs.forms.form-section>

                    {{-- Köder für Programme: Menschen sehen und füllen das Feld nicht aus. --}}
                    <div style="position:absolute;left:-10000px" aria-hidden="true">
                        <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="vdbs-btn-group">
                        <button type="button" class="vdbs-btn vdbs-btn--secondary vdbs-btn--shape-outline vdbs-btn--size-m vdbs-js-only" data-vdbs-step-prev>Zurück</button>
                        <x-vdbs.actions.button type="submit" icon="send">Antrag absenden</x-vdbs.actions.button>
                    </div>
                </div>
            </x-vdbs.forms.form>
        </section>
    @endguest

    <p style="margin-top:var(--space-8)"><x-vdbs.actions.button variant="tertiary" :href="route('portal.contact')">Kontakt aufnehmen</x-vdbs.actions.button></p>
</x-layouts.app>
