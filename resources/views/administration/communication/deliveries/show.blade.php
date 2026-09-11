@extends('layouts.administration')

@section('title', 'Versand #'.$delivery->id)

@section('content')
    @php
        $statusValue = $delivery->status->value;
        $statusType = match ($statusValue) {
            'sent' => 'success',
            'failed' => 'danger',
            default => 'info',
        };
        $statusLabel = match ($statusValue) {
            'sent' => 'Gesendet',
            'failed' => 'Fehlgeschlagen',
            default => 'Warteschlange',
        };
    @endphp

    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Kommunikation</p>
                <h1 class="page-title__title">Versand #{{ $delivery->id }}</h1>
                <p class="page-title__lead">{{ $delivery->subject }}</p>
            </div>
            <div class="page-title__actions">
                <a class="btn btn--secondary" href="{{ route('administration.communication.deliveries.index') }}">Zur Versandhistorie</a>
            </div>
        </header>

        <dl class="key-facts">
            <div>
                <dt>Status</dt>
                <dd><x-vdbs.status :type="$statusType">{{ $statusLabel }}</x-vdbs.status></dd>
            </div>
            <div><dt>Empfänger</dt><dd>{{ $delivery->recipient_email }}</dd></div>
            <div><dt>Versuche</dt><dd>{{ $delivery->attempts }}</dd></div>
            <div><dt>Eingereiht</dt><dd>{{ $delivery->queued_at?->format('d.m.Y, H:i:s') ?? '—' }}</dd></div>
        </dl>

        <section class="stack">
            <h2>Versandmetadaten</h2>
            <dl class="metadata-list">
                <div><dt>Betreff</dt><dd>{{ $delivery->subject }}</dd></div>
                <div><dt>Versandtyp</dt><dd>{{ $delivery->delivery_type->value }}</dd></div>
                <div><dt>Gesendet</dt><dd>{{ $delivery->sent_at?->format('d.m.Y, H:i:s') ?? '—' }}</dd></div>
                <div><dt>Fehlgeschlagen</dt><dd>{{ $delivery->failed_at?->format('d.m.Y, H:i:s') ?? '—' }}</dd></div>
                <div><dt>Letzte Fehlerklasse</dt><dd>{{ $delivery->last_error_class ?: '—' }}</dd></div>
                <div><dt>Auslösender Benutzer</dt><dd>{{ $delivery->sender?->email ?? 'System' }}</dd></div>
            </dl>
        </section>

        <section class="stack">
            <h2>Vorlage</h2>
            <dl class="metadata-list">
                <div><dt>Vorlagen-Key</dt><dd><code>{{ $delivery->templateVersion->template->key }}</code></dd></div>
                <div><dt>Vorlagenname</dt><dd>{{ $delivery->templateVersion->template->name }}</dd></div>
                <div><dt>Veröffentlichte Version</dt><dd>v{{ $delivery->templateVersion->version }}</dd></div>
            </dl>
            <p><a href="{{ route('administration.communication.templates.show', $delivery->templateVersion->template) }}">Vorlage öffnen</a></p>
        </section>

        <x-vdbs.notice type="info">
            Der gerenderte Nachrichtentext wird in der Versandhistorie bewusst nicht gespeichert. Die Ansicht enthält nur die für Nachvollziehbarkeit und Fehleranalyse erforderlichen Metadaten.
        </x-vdbs.notice>
    </div>
@endsection
