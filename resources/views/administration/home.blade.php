@extends('layouts.administration')

@section('title', 'Übersicht')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Verwaltung</p>
            <h1 class="page-title__title">Verwaltungsübersicht</h1>
            <p class="page-title__lead">Einstieg in Datenverwaltung, Nutzerkonten sowie Kommunikation und Öffentlichkeitsarbeit.</p>
        </header>

        @if ($canReadUsers)
            <dl class="key-facts">
                <div><dt>Konten gesamt</dt><dd>{{ $totalUsers }}</dd></div>
                <div><dt>Aktive Konten</dt><dd>{{ $activeUsers }}</dd></div>
                <div><dt>Bestätigung offen</dt><dd>{{ $pendingVerificationUsers }}</dd></div>
                <div><dt>Löschung vorgemerkt</dt><dd>{{ $pendingDeletionUsers }}</dd></div>
            </dl>
        @endif

        <section class="stack">
            <header class="stack stack--sm"><h2>Arbeitsbereiche</h2><p>Es werden nur die fachlich freigegebenen Verwaltungsbereiche angezeigt.</p></header>
            <ul class="related-links">
                @if ($canReadPersons)
                    <li><a href="{{ route('administration.persons.index') }}">Personen- und Datenverwaltung</a></li>
                @endif
                @if ($canReadUsers)
                    <li><a href="{{ route('administration.users.index') }}">Benutzerverwaltung</a></li>
                @endif
                @if ($canReadCommunication)
                    <li><a href="{{ route('administration.communication.templates.index') }}">Kommunikation und Öffentlichkeitsarbeit</a></li>
                @endif
                @if ($canReadAudit)
                    <li><a href="{{ route('administration.audit.index') }}">Audit-Protokoll</a></li>
                @endif
            </ul>
        </section>

        @if ($canReadUsers)
            <section class="stack">
                <header class="stack stack--sm"><h2>Zuletzt aktualisierte Konten</h2><p>Die zuletzt geänderten Benutzerkonten im System.</p></header>
                @if ($recentUsers->isEmpty())
                    <x-vdbs.empty-state title="Noch keine Benutzerkonten" description="Sobald Konten vorhanden sind, erscheinen sie hier." />
                @else
                    <div class="record-list">
                        @foreach ($recentUsers as $recentUser)
                            @php
                                $recentName = trim(($recentUser->person?->first_name ?? '').' '.($recentUser->person?->last_name ?? ''));
                                if ($recentName === '') {
                                    $recentName = $recentUser->email;
                                }
                            @endphp
                            <article class="record-item">
                                <div class="record-item__main">
                                    <h3 class="record-item__title"><a href="{{ route('administration.users.show', $recentUser) }}">{{ $recentName }}</a></h3>
                                    <div class="record-item__meta"><span>{{ $recentUser->email }}</span><span>Aktualisiert {{ $recentUser->updated_at->format('d.m.Y, H:i') }} Uhr</span></div>
                                </div>
                                <div class="record-item__actions"><x-vdbs.status :type="\App\Modules\Administration\Support\UserStatusPresentation::type($recentUser->status)">{{ \App\Modules\Administration\Support\UserStatusPresentation::label($recentUser->status) }}</x-vdbs.status></div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
