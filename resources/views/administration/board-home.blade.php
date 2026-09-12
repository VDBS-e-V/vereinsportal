@extends('layouts.administration')

@section('title', 'Übersicht')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorstand</p>
            <h1 class="page-title__title">Vorstandsübersicht</h1>
            <p class="page-title__lead">Mitgliederverwaltung und künftig weitere vereinsrechtliche Aufgaben des Vorstands.</p>
        </header>

        <dl class="key-facts">
            <div><dt>Mitgliedschaften gesamt</dt><dd>{{ $totalMemberships }}</dd></div>
            <div><dt>Aktiv</dt><dd>{{ $activeMemberships }}</dd></div>
            <div><dt>Geplant</dt><dd>{{ $plannedMemberships }}</dd></div>
            <div><dt>Beendet</dt><dd>{{ $endedMemberships }}</dd></div>
        </dl>

        <section class="stack">
            <header class="stack stack--sm">
                <h2>Arbeitsbereiche</h2>
                <p>Die Mitgliederverwaltung ist bereits verfügbar. Vereinsrechtliche Funktionen werden hier ergänzt.</p>
            </header>
            <ul class="related-links">
                <li><a href="{{ route('administration.memberships.index') }}">Mitgliedschaften</a></li>
            </ul>
        </section>
    </div>
@endsection
