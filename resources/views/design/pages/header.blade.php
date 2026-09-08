@extends('design.layout')

@section('title', 'Header')

@section('content')
    @php
        $publicAreas = [
            [
                'label' => 'Verwaltung',
                'url' => '#',
                'children' => [
                    ['label' => 'Mitglieder', 'url' => '#'],
                    ['label' => 'Rollen', 'url' => '#'],
                    ['label' => 'Kommunikation', 'url' => '#'],
                ],
            ],
            [
                'label' => 'Design',
                'url' => route('design.index'),
            ],
        ];

        $publicNavigation = [
            ['label' => 'Über das Portal', 'url' => '#'],
            ['label' => 'Zugang zum Portal', 'url' => '#'],
            ['label' => 'FAQ', 'url' => '#', 'active' => true],
            ['label' => 'Kontakt', 'url' => '#'],
        ];

        $exampleAccount = [
            'name' => 'Jan Brand',
            'handle' => 'jan.brand',
            'initials' => 'JB',
            'groups' => [
                [
                    ['label' => 'Mein Profil', 'icon' => 'user', 'url' => '#'],
                    ['label' => 'Kontoeinstellungen', 'icon' => 'settings', 'url' => '#'],
                    ['label' => 'Meine Tickets', 'icon' => 'ticket', 'url' => '#'],
                ],
                [
                    ['label' => 'Kontakt', 'icon' => 'mail', 'url' => '#'],
                    ['label' => 'FAQ', 'icon' => 'help', 'url' => '#'],
                    ['label' => 'Hilfe', 'icon' => 'help', 'url' => '#'],
                ],
            ],
            'logout' => [
                'label' => 'Abmelden',
                'url' => '#',
            ],
        ];
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Navigation</p>
            <h1 class="page-title__title">Portal-Header</h1>
            <p class="page-title__lead">
                Die Struktur folgt dem bewährten VDBS-Portalprinzip: oben Bereiche und
                Systeme, darunter Bereich/Seite, horizontale Seitennavigation und Konto.
                Auf Mobile bleiben die Linkgruppen im Hamburger-Menü getrennt erhalten.
            </p>
        </header>

        <section class="stack">
            <h2>Ausgeloggt</h2>
            <div class="design-header-preview">
                <x-vdbs.portal-header
                    area="VDBS Portal"
                    page-title="FAQ"
                    home-url="#"
                    :areas="$publicAreas"
                    :navigation="$publicNavigation"
                    login-url="#"
                    :breadcrumbs="[
                        ['label' => 'Start', 'url' => '#'],
                        ['label' => 'FAQ', 'url' => null],
                    ]"
                    preview
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Angemeldet</h2>
            <div class="design-header-preview">
                <x-vdbs.portal-header
                    area="VDBS Portal"
                    page-title="FAQ"
                    home-url="#"
                    :areas="$publicAreas"
                    :navigation="$publicNavigation"
                    :account="$exampleAccount"
                    preview
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Verbindliche Struktur</h2>
            <div class="table-wrapper">
                <table class="table">
                    <tbody>
                        <tr><th>Logo</th><td>Nur das VDBS-Logo auf Weiß, kompakt mit 32 px Höhe.</td></tr>
                        <tr><th>Obere Ebene</th><td>Bereiche und Systeme, zunächst Verwaltung und Design.</td></tr>
                        <tr><th>Untere Ebene</th><td>Bereich + konkrete Seite links, horizontale Seitennavigation rechts.</td></tr>
                        <tr><th>Aktiv</th><td>Green als Akzent über Textgewicht und Unterlinie.</td></tr>
                        <tr><th>Untermenüs</th><td>Eckig, weiß, klarer Rahmen und deutlicher Schatten.</td></tr>
                        <tr><th>Scroll</th><td>Obere Ebene verschwindet beim Herunterscrollen; beim Hochscrollen erscheint der ganze Header.</td></tr>
                        <tr><th>Mobile</th><td>Navigation und Bereiche wechseln gemeinsam in das Hamburger-Menü, bleiben dort aber getrennte Gruppen.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Kontomenü</h2>
            <p>
                Profil, Kontoeinstellungen, Meine Tickets, Kontakt, FAQ, Hilfe und
                Abmelden bilden die vollständige visuelle Vorlage. Funktionen ohne
                bestehende Laravel-Route bleiben in der realen Anwendung deaktiviert,
                bis ihre Fachfunktion implementiert ist.
            </p>

            <div class="cluster">
                @foreach (['user', 'settings', 'ticket', 'mail', 'help', 'logout', 'menu', 'external-link'] as $icon)
                    <span class="badge">
                        <x-vdbs.icon :name="$icon" size="18" />
                        {{ $icon }}
                    </span>
                @endforeach
            </div>
        </section>
    </div>
@endsection
