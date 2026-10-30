@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Über das Portal']];
    $supportEmail = config('portal.contact_form.recipients.support.email');
@endphp

<x-layouts.app title="Über das Portal" width="medium">
    <x-vdbs.layout.page-header title="Unser Service-Portal" lead="Smart. Vernetzt. Engagiert." :breadcrumb="$crumbs" />

    <x-vdbs.content.prose>
        <p>Wir freuen uns, Ihnen unser Service-Portal vorstellen zu dürfen: eine zentrale Anlaufstelle, die speziell entwickelt wurde, um die Zusammenarbeit zu optimieren und den Informationsaustausch für alle Beteiligten zu vereinfachen. Egal, ob Sie Teamer:in, Vereinsmitglied, Teil der Verwaltung oder eine Schule (Schüler:innen, Lehrkräfte usw.) sind: Dieses Portal bietet Ihnen maßgeschneiderte Funktionen und Zugang zu relevanten Ressourcen.</p>

        <h2>Was erwartet Sie im Portal?</h2>
        <p>Unser Verein engagiert sich intensiv in den Bereichen SV-Arbeit, Medienkompetenz, Schulbibliothek und Demokratiebildung. Das Service-Portal bündelt alle wichtigen Informationen und Tools rund um diese Themen und darüber hinaus:</p>
        <dl>
            <dt>Für Teamer:innen</dt>
            <dd>Hier finden Sie alle relevanten Materialien für Ihre Workshops, von Präsentationen und Arbeitsblättern bis hin zu organisatorischen Hinweisen und Best Practices. Eine vereinfachte Workshop-Planung und der Austausch mit anderen Teamer:innen werden ebenfalls ermöglicht.</dd>
            <dt>Für Vereinsmitglieder</dt>
            <dd>Bleiben Sie stets auf dem Laufenden über aktuelle Projekte, Termine und interne Informationen. Melden Sie sich für Veranstaltungen an, tauschen Sie sich mit anderen Mitgliedern aus und bringen Sie sich aktiv ins Vereinsleben ein.</dd>
            <dt>Für die Verwaltung</dt>
            <dd>Das Portal bietet eine effiziente Plattform für die interne Organisation und Kommunikation. Verwalten Sie Mitgliedsdaten, planen Sie Ressourcen und behalten Sie den Überblick über alle vereinsrelevanten Prozesse.</dd>
            <dt>Für Schulen (Schüler:innen, Lehrkräfte usw.)</dt>
            <dd>Schulen können sich über unser Workshop-Angebot informieren, Anfragen stellen und relevante Materialien für den Unterricht oder die SV-Arbeit herunterladen. Das Portal erleichtert die Kommunikation und Koordination zwischen den Schulen und dem Verein.</dd>
        </dl>

        <h2>Ihre Vorteile auf einen Blick</h2>
        <p>Zentraler Informationszugang · Effiziente Kommunikation · Ressourcenmanagement · Transparenz · Vereinfachte Prozesse</p>
    </x-vdbs.content.prose>

    <section id="portal-faq" aria-labelledby="portal-faq-title" style="margin-top:var(--space-8)">
        <h2 id="portal-faq-title">Häufig gestellte Fragen</h2>
        <p>In diesem Abschnitt beantworten wir die häufigsten Fragen zum VDBS Service-Portal. Haben Sie weitere Fragen oder Ergänzungen, schreiben Sie uns jederzeit über das <a href="{{ route('portal.contact') }}">Kontaktformular</a>@if ($supportEmail) oder an <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif.</p>

        <x-vdbs.disclosure.accordion>
            <x-vdbs.disclosure.accordion-item title="Was ist das Service-Portal und wofür wurde es entwickelt?" :level="3">
                <p>Das Service-Portal ist Ihre zentrale Online-Anlaufstelle für alle Belange rund um unseren Verein. Es wurde entwickelt, um die Kommunikation, den Informationsaustausch und die Organisation für Teamer:innen, Vereinsmitglieder, die Verwaltung und Schulen zu vereinfachen und zu optimieren.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Welche Themenbereiche deckt der Verein ab, die im Portal relevant sind?" :level="3">
                <p>Unser Verein ist in den Bereichen <strong>SV-Arbeit, Medienkompetenz, Schulbibliothek</strong> und <strong>Demokratiebildung</strong> aktiv. Alle Informationen, Materialien und Tools zu diesen Schwerpunkten finden Sie gebündelt im Portal.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Wer kann das Service-Portal nutzen?" :level="3">
                <p>Das Portal richtet sich an alle, die mit unserem Verein in Verbindung stehen:</p>
                <ul>
                    <li><strong>Teamer:innen:</strong> für Workshop-Materialien, Planung und Austausch.</li>
                    <li><strong>Vereinsmitglieder:</strong> für aktuelle Infos, Termine und zur aktiven Teilnahme.</li>
                    <li><strong>Verwaltung:</strong> für interne Organisation, Mitglieder- und Ressourcenmanagement.</li>
                    <li><strong>Schulen (Schüler:innen, Lehrkräfte usw.):</strong> für Infos zu Workshops, Anfragen und Lehrmaterialien.</li>
                </ul>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Welche Vorteile bietet mir das Portal?" :level="3">
                <p>Das Portal bietet Ihnen zahlreiche Vorteile, darunter:</p>
                <ul>
                    <li><strong>Zentraler Informationszugang:</strong> alle wichtigen Dokumente und Ansprechpartner an einem Ort.</li>
                    <li><strong>Effiziente Kommunikation:</strong> vereinfachter Austausch zwischen allen Beteiligten.</li>
                    <li><strong>Umfassende Ressourcen:</strong> leichter Zugriff auf Workshop-Materialien, Leitfäden und Best Practices.</li>
                    <li><strong>Transparenz:</strong> immer auf dem neuesten Stand über Vereinsaktivitäten und Projekte.</li>
                    <li><strong>Vereinfachte Prozesse:</strong> von der Workshop-Anmeldung bis zur internen Verwaltung, alles digital und unkompliziert.</li>
                </ul>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Wie erhalte ich Zugang zum Service-Portal?" :level="3">
                <p>Informationen zum Zugang (z. B. Registrierung, Login-Daten) erhalten Sie von der Vereinsverwaltung oder direkt über die Anmeldeoptionen auf der Seite <a href="{{ route('portal.access') }}">Zugang zum Portal</a>. Ihr Passwort vergessen? Fordern Sie über <a href="{{ route('password.request') }}">Passwort vergessen</a> einen Link an.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Was ist die Zwei-Faktor-Anmeldung?" :level="3">
                <p>Neben dem Passwort geben Sie bei der Anmeldung einen Code ein, entweder aus einer Authenticator-App oder per E-Mail. Das schützt Ihr Konto auch dann, wenn jemand Ihr Passwort kennt. Einrichten können Sie sie unter Konto &rarr; Sicherheit.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Wo finde ich Hilfe, wenn ich Probleme mit dem Portal habe?" :level="3">
                <p>Sollten Sie technische Schwierigkeiten haben oder Fragen zur Nutzung des Portals aufkommen, wenden Sie sich bitte über das <a href="{{ route('portal.contact') }}">Kontaktformular</a> an den Support.</p>
            </x-vdbs.disclosure.accordion-item>
        </x-vdbs.disclosure.accordion>
    </section>

    <x-vdbs.layout.section style="margin-top:var(--space-8)">
        <x-vdbs.layout.split title="Zugang zum Portal">
            <p>Hier finden Sie alle Informationen, die Sie benötigen, um auf das VDBS Serviceportal zuzugreifen. Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Wenn Sie noch kein Konto haben, können Sie hier den Zugang beantragen.</p>

            <x-slot:actions>
                <x-vdbs.actions.link-button :href="route('portal.access')">Zugang zum Portal</x-vdbs.actions.link-button>
                <x-vdbs.actions.link-button :href="route('portal.contact')" variant="all">Hilfe kontaktieren</x-vdbs.actions.link-button>
            </x-slot:actions>

            @if (filled(config('portal.home.access_image.src')))
                <x-slot:media>
                    <x-vdbs.media.figure :src="asset(config('portal.home.access_image.src'))" :alt="config('portal.home.access_image.alt')" :credit="config('portal.home.access_image.credit')" credit-placement="edge" ratio="3-2" />
                </x-slot:media>
            @endif
        </x-vdbs.layout.split>
    </x-vdbs.layout.section>
</x-layouts.app>
