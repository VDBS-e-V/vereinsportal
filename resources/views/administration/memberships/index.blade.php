@extends('layouts.administration')

@section('title', 'Mitgliedschaften')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Verwaltung</p>
            <h1 class="page-title__title">Mitgliedschaften</h1>
            <p class="page-title__lead">Mitgliedschaftszeiträume finden, Status nachvollziehen und Personen öffnen.</p>
        </header>

        <form class="search-form" action="{{ route('administration.memberships.index') }}" method="get">
            <div class="search-form__field">
                <label for="membership-search">Mitgliedschaften durchsuchen</label>
                <input class="form__control" id="membership-search" name="q" type="search" value="{{ $search }}" placeholder="Name oder E-Mail-Adresse">
            </div>
            <div class="search-form__field">
                <label for="membership-status">Status</label>
                <select class="form__control" id="membership-status" name="status">
                    <option value="">Alle</option>
                    @foreach (\App\Modules\Membership\Enums\MembershipStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected($status === $option)>
                            {{ \App\Modules\Administration\Support\MembershipStatusPresentation::label($option) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="search-form__actions">
                <button class="btn btn--secondary" type="submit">Filtern</button>
                @if ($search !== '' || $status !== null)
                    <a class="btn btn--quiet" href="{{ route('administration.memberships.index') }}">Zurücksetzen</a>
                @endif
            </div>
        </form>

        <div class="table-toolbar">
            <div class="table-toolbar__primary">
                <span class="table-toolbar__summary">{{ $memberships->total() }} {{ $memberships->total() === 1 ? 'Mitgliedschaft' : 'Mitgliedschaften' }}</span>
            </div>
        </div>

        @if ($memberships->isEmpty())
            <x-vdbs.empty-state title="Keine Mitgliedschaften gefunden" description="Passen Sie Suche oder Statusfilter an oder öffnen Sie eine Person, um eine Mitgliedschaft anzulegen." />
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Person</th><th>Zeitraum</th><th>Status</th><th>Portalzugang</th><th>Aktion</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($memberships as $membership)
                            @php
                                $membershipStatus = $membership->status();
                                $person = $membership->person;
                            @endphp
                            <tr>
                                <td>{{ trim($person->first_name.' '.$person->last_name) }}<br><small>{{ $person->email }}</small></td>
                                <td>{{ $membership->starts_on->format('d.m.Y') }} – {{ $membership->ends_on?->format('d.m.Y') ?? 'offen' }}</td>
                                <td>
                                    <x-vdbs.status :type="\App\Modules\Administration\Support\MembershipStatusPresentation::type($membershipStatus)">
                                        {{ \App\Modules\Administration\Support\MembershipStatusPresentation::label($membershipStatus) }}
                                    </x-vdbs.status>
                                </td>
                                <td>{{ $person->user !== null ? 'Vorhanden' : 'Kein Konto' }}</td>
                                <td class="table__actions"><a href="{{ route('administration.memberships.show', $membership) }}">Öffnen</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($memberships->hasPages())
                <nav class="pagination" aria-label="Seitennavigation">
                    <p class="pagination__summary">Einträge {{ $memberships->firstItem() }}–{{ $memberships->lastItem() }} von {{ $memberships->total() }}</p>
                    <ul class="pagination__list">
                        <li>
                            @if ($memberships->onFirstPage())
                                <span class="pagination__disabled">Zurück</span>
                            @else
                                <a class="pagination__link" href="{{ $memberships->previousPageUrl() }}">Zurück</a>
                            @endif
                        </li>
                        <li><span class="pagination__current" aria-current="page">Seite {{ $memberships->currentPage() }} von {{ $memberships->lastPage() }}</span></li>
                        <li>
                            @if ($memberships->hasMorePages())
                                <a class="pagination__link" href="{{ $memberships->nextPageUrl() }}">Weiter</a>
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
