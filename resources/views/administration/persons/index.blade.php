@extends('layouts.administration')

@section('title', 'Personen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Verwaltung</p>
                <h1 class="page-title__title">Personen</h1>
                <p class="page-title__lead">Personenstammdaten finden und vorhandene Portalzugänge nachvollziehen.</p>
            </div>

            @if ($canManage)
                <div class="page-title__actions">
                    <a class="btn" href="{{ route('administration.persons.create') }}">Person anlegen</a>
                </div>
            @endif
        </header>

        <form class="search-form" action="{{ route('administration.persons.index') }}" method="get">
            <div class="search-form__field">
                <label for="administration-person-search">Personen durchsuchen</label>
                <input class="form__control" id="administration-person-search" name="q" type="search" value="{{ $search }}" placeholder="Name oder E-Mail-Adresse">
            </div>

            <div class="search-form__actions">
                <button class="btn btn--secondary" type="submit">Suchen</button>

                @if ($search !== '')
                    <a class="btn btn--quiet" href="{{ route('administration.persons.index') }}">Zurücksetzen</a>
                @endif
            </div>
        </form>

        <div class="table-toolbar">
            <div class="table-toolbar__primary">
                <span class="table-toolbar__summary">{{ $persons->total() }} {{ $persons->total() === 1 ? 'Person' : 'Personen' }}</span>
            </div>
        </div>

        @if ($persons->isEmpty())
            <x-vdbs.empty-state title="Keine Personen gefunden" :description="$search !== '' ? 'Passen Sie die Suche an.' : 'Es sind noch keine Personen gespeichert.'">
                @if ($canManage)
                    <x-slot:actions>
                        <a class="btn" href="{{ route('administration.persons.create') }}">Person anlegen</a>
                    </x-slot:actions>
                @endif
            </x-vdbs.empty-state>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>E-Mail-Adresse</th>
                            <th>Geburtsdatum</th>
                            <th>Portalzugang</th>
                            <th>Ort</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($persons as $listedPerson)
                            @php
                                $displayName = trim($listedPerson->first_name.' '.($listedPerson->name_addition !== null ? $listedPerson->name_addition.' ' : '').$listedPerson->last_name);
                            @endphp
                            <tr>
                                <td>{{ $displayName }}</td>
                                <td>{{ $listedPerson->email }}</td>
                                <td>{{ $listedPerson->birth_date->format('d.m.Y') }}</td>
                                <td>
                                    @if ($listedPerson->user !== null)
                                        <x-vdbs.status :type="\App\Modules\Administration\Support\UserStatusPresentation::type($listedPerson->user->status)">
                                            {{ \App\Modules\Administration\Support\UserStatusPresentation::label($listedPerson->user->status) }}
                                        </x-vdbs.status>
                                    @else
                                        <x-vdbs.status type="info">Kein Konto</x-vdbs.status>
                                    @endif
                                </td>
                                <td>{{ trim(($listedPerson->postal_code ?? '').' '.($listedPerson->city ?? '')) ?: '—' }}</td>
                                <td class="table__actions">
                                    <a href="{{ route('administration.persons.show', $listedPerson) }}">Öffnen</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($persons->hasPages())
                <nav class="pagination" aria-label="Seitennavigation">
                    <p class="pagination__summary">Einträge {{ $persons->firstItem() }}–{{ $persons->lastItem() }} von {{ $persons->total() }}</p>
                    <ul class="pagination__list">
                        <li>
                            @if ($persons->onFirstPage())
                                <span class="pagination__disabled">Zurück</span>
                            @else
                                <a class="pagination__link" href="{{ $persons->previousPageUrl() }}">Zurück</a>
                            @endif
                        </li>
                        <li>
                            <span class="pagination__current" aria-current="page">Seite {{ $persons->currentPage() }} von {{ $persons->lastPage() }}</span>
                        </li>
                        <li>
                            @if ($persons->hasMorePages())
                                <a class="pagination__link" href="{{ $persons->nextPageUrl() }}">Weiter</a>
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
