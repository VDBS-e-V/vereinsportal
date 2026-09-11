@extends('layouts.administration')

@section('title', 'Versandhistorie')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Kommunikation</p>
                <h1 class="page-title__title">Versandhistorie</h1>
                <p class="page-title__lead">System-E-Mails mit Status, Vorlage und Versandzeitpunkt. Inhalte der versendeten Nachricht werden hier bewusst nicht gespeichert oder angezeigt.</p>
            </div>
            <div class="page-title__actions">
                <a class="btn btn--secondary" href="{{ route('administration.communication.templates.index') }}">Zu den Vorlagen</a>
            </div>
        </header>

        <form class="form" method="GET" action="{{ route('administration.communication.deliveries.index') }}">
            <div class="form__field">
                <label class="form__label" for="delivery-search">Suche</label>
                <input class="form__control" id="delivery-search" name="q" type="search" value="{{ $search }}" placeholder="Empfänger oder Betreff">
            </div>

            <div class="form__field">
                <label class="form__label" for="delivery-status">Status</label>
                <select class="form__control" id="delivery-status" name="status">
                    <option value="">Alle Status</option>
                    @foreach ($statuses as $deliveryStatus)
                        <option value="{{ $deliveryStatus->value }}" @selected($status === $deliveryStatus->value)>
                            {{ match ($deliveryStatus->value) {
                                'queued' => 'Warteschlange',
                                'sent' => 'Gesendet',
                                'failed' => 'Fehlgeschlagen',
                                default => $deliveryStatus->value,
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form__field">
                <label class="form__label" for="delivery-template">Vorlagen-Key</label>
                <input class="form__control" id="delivery-template" name="template" value="{{ $template }}" placeholder="z. B. auth.portal-invitation">
            </div>

            <div class="form__actions">
                <button class="btn btn--secondary" type="submit">Filtern</button>
                <a class="btn btn--quiet" href="{{ route('administration.communication.deliveries.index') }}">Zurücksetzen</a>
            </div>
        </form>

        <div class="table-toolbar">
            <div class="table-toolbar__primary">
                <span class="table-toolbar__summary">{{ $deliveries->total() }} {{ $deliveries->total() === 1 ? 'Versand' : 'Versände' }}</span>
            </div>
        </div>

        @if ($deliveries->isEmpty())
            <x-vdbs.empty-state title="Keine Versände gefunden" description="Für die gewählten Filter sind keine Versandvorgänge vorhanden." />
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Zeitpunkt</th>
                            <th>Empfänger</th>
                            <th>Vorlage</th>
                            <th>Status</th>
                            <th>Versuche</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveries as $delivery)
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
                            <tr>
                                <td>{{ $delivery->queued_at?->format('d.m.Y, H:i:s') ?? '—' }}</td>
                                <td>{{ $delivery->recipient_email }}</td>
                                <td>
                                    <code>{{ $delivery->templateVersion->template->key }}</code>
                                    <span> · v{{ $delivery->templateVersion->version }}</span>
                                </td>
                                <td><x-vdbs.status :type="$statusType">{{ $statusLabel }}</x-vdbs.status></td>
                                <td>{{ $delivery->attempts }}</td>
                                <td class="table__actions"><a href="{{ route('administration.communication.deliveries.show', $delivery) }}">Details</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($deliveries->hasPages())
                <nav class="pagination" aria-label="Seitennavigation">
                    <p class="pagination__summary">Einträge {{ $deliveries->firstItem() }}–{{ $deliveries->lastItem() }} von {{ $deliveries->total() }}</p>
                    <ul class="pagination__list">
                        <li>
                            @if ($deliveries->onFirstPage())
                                <span class="pagination__disabled">Zurück</span>
                            @else
                                <a class="pagination__link" href="{{ $deliveries->previousPageUrl() }}">Zurück</a>
                            @endif
                        </li>
                        <li><span class="pagination__current" aria-current="page">Seite {{ $deliveries->currentPage() }} von {{ $deliveries->lastPage() }}</span></li>
                        <li>
                            @if ($deliveries->hasMorePages())
                                <a class="pagination__link" href="{{ $deliveries->nextPageUrl() }}">Weiter</a>
                            @else
                                <span class="pagination__disabled">Weiter</span>
                            @endif
                        </li>
                    </ul>
                </nav>
            @endif
        @endif
    </div>
@endsection
