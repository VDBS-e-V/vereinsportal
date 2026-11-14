@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Über das Portal']];
    $supportEmail = config('portal.contact_form.recipients.support.email');
@endphp

<x-layouts.app title="Über das Portal" width="medium">
    <x-vdbs.layout.page-header title="Unser Vereinsportal" lead="Smart. Vernetzt. Engagiert." :breadcrumb="$crumbs" />

    <x-vdbs.content.prose>
        <p>Wir freuen uns, Ihnen unser Vereinsportal vorstellen zu dürfen: eine zentrale Anlaufstelle, die speziell entwickelt wurde, um die Zusammenarbeit zu optimieren und den Informationsaustausch für alle Beteiligten zu vereinfachen. Egal, ob Sie Teamer:in, Vereinsmitglied, Teil der Verwaltung oder eine Schule (Schüler:innen, Lehrkräfte usw.) sind: Dieses Portal bietet Ihnen maßgeschneiderte Funktionen und Zugang zu relevanten Ressourcen.</p>

        <h2>Was erwartet Sie im Portal?</h2>
        <p>Unser Verein engagiert sich intensiv in den Bereichen SV-Arbeit, Medienkompetenz, Schulbibliothek und Demokratiebildung. Das Vereinsportal bündelt alle wichtigen Informationen und Tools rund um diese Themen und darüber hinaus:</p>
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
        <p>Die häufigsten Fragen zum VDBS Vereinsportal beantworten wir auf einer eigenen Seite, nach Themen geordnet und mit Suche. Haben Sie weitere Fragen oder Ergänzungen, schreiben Sie uns jederzeit über das <a href="{{ route('portal.contact') }}">Kontaktformular</a>@if ($supportEmail) oder an <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif.</p>

        <p><x-vdbs.actions.button :href="route('portal.faq')" icon="arrow-right" icon-position="end">Alle Fragen und Antworten</x-vdbs.actions.button></p>
    </section>

    <x-vdbs.layout.section style="margin-top:var(--space-8)">
        <x-vdbs.layout.split title="Zugang zum Portal">
            <p>Hier finden Sie alle Informationen, die Sie benötigen, um auf das VDBS Vereinsportal zuzugreifen. Wenn Sie bereits ein Konto haben, können Sie sich hier anmelden. Wenn Sie noch kein Konto haben, können Sie hier den Zugang beantragen.</p>

            <x-slot:actions>
                <x-vdbs.actions.button :href="route('portal.access')" variant="primary" icon="arrow-right" icon-position="end">Zugang zum Portal</x-vdbs.actions.button>
                <x-vdbs.actions.button :href="route('portal.contact')" variant="text" icon="arrow-right" icon-position="end">Hilfe kontaktieren</x-vdbs.actions.button>
            </x-slot:actions>

            @if (filled(config('portal.home.access_image.src')))
                <x-slot:media>
                    <x-vdbs.media.figure :src="asset(config('portal.home.access_image.src'))" :alt="config('portal.home.access_image.alt')" :credit="config('portal.home.access_image.credit')" credit-placement="edge" ratio="3-2" />
                </x-slot:media>
            @endif
        </x-vdbs.layout.split>
    </x-vdbs.layout.section>
</x-layouts.app>
