<x-layouts.app title="Bewerbung bestätigt" width="narrow">
    <x-vdbs.layout.page-header title="Danke, Ihre Bewerbung zählt" lead="{{ $application->seminar->title }}" :breadcrumb="[['label' => 'Start', 'href' => route('home')], ['label' => 'Bewerbung']]" />
    <p>Die Koordination sichtet die Bewerbungen nach dem Stichtag{{ $application->seminar->application_deadline ? ' ('.$application->seminar->application_deadline->format('d.m.Y').')' : '' }} und meldet sich bei Ihnen per E-Mail, ob Sie im Team sind.</p>
    <p>Möchten Sie Ihre Bewerbung zurückziehen, antworten Sie einfach auf eine Nachricht der Koordination oder schreiben Sie uns über die <a href="{{ route('portal.contact') }}">Kontaktseite</a>.</p>
</x-layouts.app>
