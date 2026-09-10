@extends('layouts.administration')

@section('title', $displayName)

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm"><p class="page-title__kicker">Personenverwaltung</p><h1 class="page-title__title">{{ $displayName }}</h1><p class="page-title__lead">{{ $person->email }}</p></div>
            <div class="page-title__actions">@if ($canManage)<a class="btn" href="{{ route('administration.persons.edit', $person) }}">Bearbeiten</a>@endif<a class="btn btn--secondary" href="{{ route('administration.persons.index') }}">Zur Personenliste</a></div>
        </header>
        <dl class="key-facts">
            <div><dt>Geburtsdatum</dt><dd>{{ $person->birth_date->format('d.m.Y') }}</dd></div>
            <div><dt>Mitgliedschaften</dt><dd>{{ $memberships->count() }}</dd></div>
            <div><dt>Portalzugang</dt><dd>@if ($person->user !== null)<x-vdbs.status :type="\App\Modules\Administration\Support\UserStatusPresentation::type($person->user->status)">{{ \App\Modules\Administration\Support\UserStatusPresentation::label($person->user->status) }}</x-vdbs.status>@else<x-vdbs.status type="info">Kein Konto</x-vdbs.status>@endif</dd></div>
            <div><dt>Zuletzt geändert</dt><dd>{{ $person->updated_at->format('d.m.Y, H:i') }} Uhr</dd></div>
        </dl>
        <section class="stack"><h2>Persönliche Daten</h2><dl class="metadata-list"><div><dt>Titel</dt><dd>{{ $person->title ?: '—' }}</dd></div><div><dt>Vorname</dt><dd>{{ $person->first_name }}</dd></div><div><dt>Namenszusatz</dt><dd>{{ $person->name_addition ?: '—' }}</dd></div><div><dt>Nachname</dt><dd>{{ $person->last_name }}</dd></div><div><dt>Geburtsdatum</dt><dd>{{ $person->birth_date->format('d.m.Y') }}</dd></div></dl></section>
        <section class="stack"><h2>Kontakt</h2><dl class="metadata-list"><div><dt>E-Mail-Adresse</dt><dd>{{ $person->email }}</dd></div><div><dt>Telefonnummer</dt><dd>{{ $person->phone ?: '—' }}</dd></div></dl></section>
        <section class="stack"><h2>Adresse</h2><dl class="metadata-list"><div><dt>Straße und Hausnummer</dt><dd>{{ trim(($person->street ?? '').' '.($person->house_number ?? '')) ?: '—' }}</dd></div><div><dt>Postleitzahl und Ort</dt><dd>{{ trim(($person->postal_code ?? '').' '.($person->city ?? '')) ?: '—' }}</dd></div><div><dt>Ländercode</dt><dd>{{ $person->country_code }}</dd></div></dl></section>
        <section class="stack">
            <header class="page-title page-title--split">
                <div class="stack stack--sm"><h2>Mitgliedschaftsverlauf</h2><p>Historische und aktuelle Mitgliedschaftszeiträume dieser Person.</p></div>
                @if ($canManage)<div class="page-title__actions"><a class="btn" href="{{ route('administration.persons.memberships.create', $person) }}">Mitgliedschaft anlegen</a></div>@endif
            </header>
            @if ($memberships->isEmpty())
                <x-vdbs.empty-state title="Keine Mitgliedschaft vorhanden" description="Für diese Person wurde noch kein Mitgliedschaftszeitraum gespeichert." />
            @else
                <div class="record-list">
                    @foreach ($memberships as $membership)
                        @php $membershipStatus = $membership->status(); @endphp
                        <article class="record-item">
                            <div class="record-item__main">
                                <h3 class="record-item__title"><a href="{{ route('administration.memberships.show', $membership) }}">Mitgliedschaft ab {{ $membership->starts_on->format('d.m.Y') }}</a></h3>
                                <div class="record-item__meta"><span>Beginn {{ $membership->starts_on->format('d.m.Y') }}</span><span>Ende {{ $membership->ends_on?->format('d.m.Y') ?? 'offen' }}</span></div>
                            </div>
                            <div class="record-item__actions"><x-vdbs.status :type="\App\Modules\Administration\Support\MembershipStatusPresentation::type($membershipStatus)">{{ \App\Modules\Administration\Support\MembershipStatusPresentation::label($membershipStatus) }}</x-vdbs.status></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
        <section class="stack">
            <header class="stack stack--sm"><h2>Portalzugang</h2><p>Personendatensatz und Benutzerkonto bleiben fachlich getrennte Objekte.</p></header>
            @if ($person->user !== null)
                <div class="panel stack"><dl class="metadata-list"><div><dt>Konto-E-Mail</dt><dd>{{ $person->user->email }}</dd></div><div><dt>Status</dt><dd><x-vdbs.status :type="\App\Modules\Administration\Support\UserStatusPresentation::type($person->user->status)">{{ \App\Modules\Administration\Support\UserStatusPresentation::label($person->user->status) }}</x-vdbs.status></dd></div></dl><div><a href="{{ route('administration.users.show', $person->user) }}">Benutzerkonto öffnen</a></div></div>
            @else
                <x-vdbs.empty-state title="Kein Benutzerkonto verknüpft" description="Ein Portalzugang wird in diesem Entwicklungsblock nicht automatisch angelegt." />
            @endif
        </section>
    </div>
@endsection
