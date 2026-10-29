@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Über das Portal']];
@endphp

<x-layouts.app title="Über das Portal" width="medium">
    <x-vdbs.layout.page-header title="Über das Portal" lead="Das Vereinsportal ist die zentrale Anlaufstelle für Mitglieder, Teamende und Schulen des VDBS." :breadcrumb="$crumbs" />

    <x-vdbs.content.prose>
        <h2>Wofür das Portal da ist</h2>
        <p>Im Portal finden Sie Ihr Konto, Ihre Sicherheitseinstellungen und die Bereiche, die für Ihre Rolle im Verein vorgesehen sind. Welche Bereiche Sie sehen, richtet sich nach Ihrer Rolle.</p>
        <h2>Datenschutz und Sicherheit</h2>
        <p>Der Zugang ist durch Ihr Passwort geschützt, auf Wunsch zusätzlich durch eine Zwei-Faktor-Anmeldung. Verwaltungsaktionen werden nachvollziehbar protokolliert.</p>
    </x-vdbs.content.prose>

    <section id="portal-faq" aria-labelledby="portal-faq-title" style="margin-top:var(--space-8)">
        <h2 id="portal-faq-title">Häufige Fragen</h2>

        <x-vdbs.disclosure.accordion>
            <x-vdbs.disclosure.accordion-item title="Wie erhalte ich ein Konto?" :level="3">
                <p>Ein Konto legt die Geschäftsstelle des Vereins für Sie an. Sie erhalten eine E-Mail mit einem Link, über den Sie Ihr Passwort festlegen. Der Link ist sieben Tage gültig. Mehr dazu finden Sie unter <a href="{{ route('portal.access') }}">Zugang zum Portal</a>.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Ich habe mein Passwort vergessen. Was kann ich tun?" :level="3">
                <p>Fordern Sie über <a href="{{ route('password.request') }}">Passwort vergessen</a> einen Link an. Er wird an Ihre hinterlegte E-Mail-Adresse gesendet und ist 60 Minuten gültig.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Was ist die Zwei-Faktor-Anmeldung?" :level="3">
                <p>Neben dem Passwort geben Sie bei der Anmeldung einen Code ein, entweder aus einer Authenticator-App oder per E-Mail. Das schützt Ihr Konto auch dann, wenn jemand Ihr Passwort kennt. Einrichten können Sie sie unter Konto &rarr; Sicherheit.</p>
            </x-vdbs.disclosure.accordion-item>
            <x-vdbs.disclosure.accordion-item title="Wen kontaktiere ich bei Problemen?" :level="3">
                <p>Die Kontaktmöglichkeiten finden Sie unter <a href="{{ route('portal.contact') }}">Kontakt</a>.</p>
            </x-vdbs.disclosure.accordion-item>
        </x-vdbs.disclosure.accordion>
    </section>
</x-layouts.app>
