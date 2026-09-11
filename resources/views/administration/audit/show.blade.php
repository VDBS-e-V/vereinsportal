@extends('layouts.administration')

@section('title', 'Audit #'.$auditEvent->id)

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Audit</p>
                <h1 class="page-title__title">{{ $auditEvent->event_key }}</h1>
                <p class="page-title__lead">Ereignis #{{ $auditEvent->id }} vom {{ $auditEvent->occurred_at->format('d.m.Y, H:i:s') }} Uhr</p>
            </div>
            <div class="page-title__actions"><a class="btn btn--secondary" href="{{ route('administration.audit.index') }}">Zur Audit-Liste</a></div>
        </header>

        <dl class="key-facts">
            <div><dt>Zeitpunkt</dt><dd>{{ $auditEvent->occurred_at->format('d.m.Y, H:i:s') }} Uhr</dd></div>
            <div><dt>Akteur</dt><dd>{{ \App\Modules\Administration\Support\AuditEventPresentation::actorLabel($auditEvent) }}</dd></div>
            <div><dt>Objekt</dt><dd>{{ \App\Modules\Administration\Support\AuditEventPresentation::subjectLabel($auditEvent) }}</dd></div>
        </dl>

        @if ($subjectUrl !== null)
            <section class="stack">
                <h2>Verknüpftes Fachobjekt</h2>
                <p><a href="{{ $subjectUrl }}">{{ \App\Modules\Administration\Support\AuditEventPresentation::subjectLabel($auditEvent) }} öffnen</a></p>
            </section>
        @endif

        @if ($auditEvent->comment !== null && trim($auditEvent->comment) !== '')
            <section class="stack">
                <h2>Begründung / Kommentar</h2>
                <p>{{ $auditEvent->comment }}</p>
            </section>
        @endif

        <section class="stack">
            <h2>Änderung</h2>
            @if (($auditEvent->old_values ?? []) === [] && ($auditEvent->new_values ?? []) === [])
                <x-vdbs.empty-state title="Keine fachlichen Werte gespeichert" description="Für dieses Ereignis wurden keine whitelisted Vorher-/Nachher-Werte protokolliert." />
            @else
                <div class="table-wrapper">
                    <table class="table">
                        <thead><tr><th>Feld</th><th>Vorher</th><th>Nachher</th></tr></thead>
                        <tbody>
                            @foreach (collect(array_keys(array_merge($auditEvent->old_values ?? [], $auditEvent->new_values ?? [])))->unique()->sort()->values() as $field)
                                <tr>
                                    <th scope="row">{{ $field }}</th>
                                    <td>{{ \App\Modules\Administration\Support\AuditEventPresentation::value(($auditEvent->old_values ?? [])[$field] ?? null) }}</td>
                                    <td>{{ \App\Modules\Administration\Support\AuditEventPresentation::value(($auditEvent->new_values ?? [])[$field] ?? null) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <x-vdbs.notice type="info">
            Technische Request-Metadaten wie IP-Adresse, User-Agent und Device-Informationen werden in dieser Oberfläche bewusst nicht angezeigt.
        </x-vdbs.notice>
    </div>
@endsection
