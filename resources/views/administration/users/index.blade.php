@extends('layouts.administration')

@section('title', 'Benutzer')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Verwaltung</p>
            <h1 class="page-title__title">Benutzerverwaltung</h1>
            <p class="page-title__lead">
                Konten suchen, nach Status filtern und vorhandene Kontodaten einsehen.
            </p>
        </header>

        <form class="search-form" action="{{ route('administration.users.index') }}" method="get">
            <div class="search-form__field">
                <label for="administration-user-search">Benutzer durchsuchen</label>
                <input
                    class="form__control"
                    id="administration-user-search"
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Name oder E-Mail-Adresse"
                >
            </div>

            <div class="search-form__field">
                <label for="administration-user-status">Status</label>
                <select
                    class="form__control"
                    id="administration-user-status"
                    name="status"
                >
                    <option value="">Alle Status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="search-form__actions">
                <button class="btn btn--secondary" type="submit">Anwenden</button>
                @if ($search !== '' || $status !== '')
                    <a class="btn btn--quiet" href="{{ route('administration.users.index') }}">
                        Zurücksetzen
                    </a>
                @endif
            </div>
        </form>

        <div class="table-toolbar">
            <div class="table-toolbar__primary">
                <span class="table-toolbar__summary">
                    {{ $users->total() }} {{ $users->total() === 1 ? 'Konto' : 'Konten' }}
                </span>
            </div>
        </div>

        @if ($users->isEmpty())
            <x-vdbs.empty-state
                title="Keine Konten gefunden"
                description="Passen Sie Suche oder Statusfilter an."
            >
                <x-slot:actions>
                    <a class="btn btn--secondary" href="{{ route('administration.users.index') }}">
                        Filter zurücksetzen
                    </a>
                </x-slot:actions>
            </x-vdbs.empty-state>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail-Adresse</th>
                            <th>Status</th>
                            <th>Letzte Anmeldung</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $listedUser)
                            @php
                                $displayName = trim(
                                    ($listedUser->person?->first_name ?? '').' '.
                                    ($listedUser->person?->last_name ?? '')
                                );
                            @endphp

                            <tr>
                                <td>
                                    {{ $displayName !== '' ? $displayName : '—' }}
                                </td>
                                <td>{{ $listedUser->email }}</td>
                                <td>
                                    <x-vdbs.status
                                        :type="\App\Modules\Administration\Support\UserStatusPresentation::type($listedUser->status)"
                                    >
                                        {{ \App\Modules\Administration\Support\UserStatusPresentation::label($listedUser->status) }}
                                    </x-vdbs.status>
                                </td>
                                <td>
                                    @if ($listedUser->last_login_at !== null)
                                        {{ $listedUser->last_login_at->format('d.m.Y, H:i') }} Uhr
                                    @else
                                        Noch nie
                                    @endif
                                </td>
                                <td class="table__actions">
                                    <a href="{{ route('administration.users.show', $listedUser) }}">
                                        Öffnen
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <nav class="pagination" aria-label="Seitennavigation">
                    <p class="pagination__summary">
                        Einträge {{ $users->firstItem() }}–{{ $users->lastItem() }}
                        von {{ $users->total() }}
                    </p>
                    <ul class="pagination__list">
                        <li>
                            @if ($users->onFirstPage())
                                <span class="pagination__disabled">Zurück</span>
                            @else
                                <a class="pagination__link" href="{{ $users->previousPageUrl() }}">
                                    Zurück
                                </a>
                            @endif
                        </li>
                        <li>
                            <span class="pagination__current" aria-current="page">
                                Seite {{ $users->currentPage() }} von {{ $users->lastPage() }}
                            </span>
                        </li>
                        <li>
                            @if ($users->hasMorePages())
                                <a class="pagination__link" href="{{ $users->nextPageUrl() }}">
                                    Weiter
                                </a>
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
