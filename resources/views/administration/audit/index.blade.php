@extends('layouts.administration')

@section('title', 'Audit')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Administration</p>
            <h1 class="page-title__title">Audit-Protokoll</h1>
            <p class="page-title__lead">Nachvollziehbare Fach- und Sicherheitsereignisse. Technische Request- und Device-Metadaten werden hier bewusst nicht angezeigt.</p>
        </header>

        <form class="form" method="GET" action="{{ route('administration.audit.index') }}">
            <div class="form__field">
                <label for="audit-event-key">Ereignis</label>
                <select class="form__control" id="audit-event-key" name="event_key">
                    <option value="">Alle Ereignisse</option>
                    @foreach ($eventKeys as $key)
                        <option value="{{ $key }}" @selected($filters['event_key'] === $key)>{{ $key }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form__field">
                <label for="audit-actor">Akteur</label>
                <select class="form__control" id="audit-actor" name="actor_id">
                    <option value="">Alle Benutzer</option>
                    @foreach ($actors as $filterActor)
                        @php
                            $filterActorName = trim(
                                ($filterActor->person?->first_name ?? '').' '.
                                ($filterActor->person?->last_name ?? ''),
                            );
                        @endphp
                        <option value="{{ $filterActor->id }}" @selected($filters['actor_id'] === (string) $filterActor->id)>
                            {{ $filterActorName !== '' ? $filterActorName.' · ' : '' }}{{ $filterActor->email }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form__field">
                <label for="audit-subject-type">Objekttyp</label>
                <select class="form__control" id="audit-subject-type" name="subject_type">
                    <option value="">Alle Objekttypen</option>
                    @foreach ($subjectTypes as $type)
                        <option value="{{ $type }}" @selected($filters['subject_type'] === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form__field">
                <label for="audit-subject-id">Objekt-ID</label>
                <input class="form__control" id="audit-subject-id" name="subject_id" inputmode="numeric" value="{{ $filters['subject_id'] }}">
            </div>
            <div class="form__field">
                <label for="audit-from">Von</label>
                <input class="form__control" id="audit-from" name="from" type="date" value="{{ $filters['from'] }}">
            </div>
            <div class="form__field">
                <label for="audit-to">Bis</label>
                <input class="form__control" id="audit-to" name="to" type="date" value="{{ $filters['to'] }}">
            </div>
            <div class="form__actions">
                <button class="btn btn--secondary" type="submit">Filtern</button>
                <a class="btn btn--quiet" href="{{ route('administration.audit.index') }}">Zurücksetzen</a>
            </div>
        </form>

        <div class="table-toolbar">
            <div class="table-toolbar__primary">
                <span class="table-toolbar__summary">{{ $events->total() }} {{ $events->total() === 1 ? 'Ereignis' : 'Ereignisse' }}</span>
            </div>
        </div>

        @if ($events->isEmpty())
            <x-vdbs.empty-state title="Keine Audit-Ereignisse gefunden" description="Passen Sie die Filter an." />
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Zeitpunkt</th><th>Ereignis</th><th>Akteur</th><th>Objekt</th><th>Aktion</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>{{ $event->occurred_at->format('d.m.Y, H:i:s') }}</td>
                                <td><code>{{ $event->event_key }}</code></td>
                                <td>{{ \App\Modules\Administration\Support\AuditEventPresentation::actorLabel($event) }}</td>
                                <td>{{ \App\Modules\Administration\Support\AuditEventPresentation::subjectLabel($event) }}</td>
                                <td class="table__actions"><a href="{{ route('administration.audit.show', $event) }}">Details</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <nav class="pagination" aria-label="Seitennavigation">
                    <p class="pagination__summary">Einträge {{ $events->firstItem() }}–{{ $events->lastItem() }} von {{ $events->total() }}</p>
                    <ul class="pagination__list">
                        <li>
                            @if ($events->onFirstPage())
                                <span class="pagination__disabled">Zurück</span>
                            @else
                                <a class="pagination__link" href="{{ $events->previousPageUrl() }}">Zurück</a>
                            @endif
                        </li>
                        <li><span class="pagination__current" aria-current="page">Seite {{ $events->currentPage() }} von {{ $events->lastPage() }}</span></li>
                        <li>
                            @if ($events->hasMorePages())
                                <a class="pagination__link" href="{{ $events->nextPageUrl() }}">Weiter</a>
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
