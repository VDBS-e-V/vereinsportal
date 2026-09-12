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

            @if ($canManagePersons)
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
                @if ($canManagePersons)
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
                            <th>Status</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Ort</th>
                            <th>Letzte Anmeldung</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($persons as $listedPerson)
                            @php
                                $displayName = trim($listedPerson->first_name.' '.($listedPerson->name_addition !== null ? $listedPerson->name_addition.' ' : '').$listedPerson->last_name);
                                $listedUser = $listedPerson->user;
                                $canResetPassword = $canManageUserStatus
                                    && $listedUser !== null
                                    && $listedUser->status === \App\Modules\Identity\Enums\UserStatus::Active
                                    && $listedUser->email_verified_at !== null;
                                $canLockAccount = $canManageUserStatus
                                    && $listedUser !== null
                                    && $listedUser->status === \App\Modules\Identity\Enums\UserStatus::Active
                                    && $listedUser->id !== $actorUserId;
                            @endphp
                            <tr>
                                <td style="vertical-align: middle;">
                                    @if ($listedUser !== null)
                                        <x-vdbs.status :type="\App\Modules\Administration\Support\UserStatusPresentation::type($listedUser->status)">
                                            {{ \App\Modules\Administration\Support\UserStatusPresentation::label($listedUser->status) }}
                                        </x-vdbs.status>
                                    @else
                                        <x-vdbs.status type="info">Kein Konto</x-vdbs.status>
                                    @endif
                                </td>
                                <td style="vertical-align: middle;">{{ $displayName }}</td>
                                <td style="vertical-align: middle;">{{ $listedPerson->email }}</td>
                                <td style="vertical-align: middle;">{{ trim(($listedPerson->postal_code ?? '').' '.($listedPerson->city ?? '')) ?: '—' }}</td>
                                <td style="vertical-align: middle;">
                                    @if ($listedUser === null)
                                        —
                                    @elseif ($listedUser->last_login_at !== null)
                                        {{ $listedUser->last_login_at->format('d.m.Y, H:i') }} Uhr
                                    @else
                                        Noch nie
                                    @endif
                                </td>
                                <td class="table__actions" style="vertical-align: middle;">
                                    <div class="button-group" aria-label="Aktionen für {{ $displayName }}">
                                        <a
                                            class="btn btn--secondary btn--icon btn--sm"
                                            href="{{ route('administration.persons.show', $listedPerson) }}"
                                            title="Ansehen"
                                            aria-label="{{ $displayName }} ansehen"
                                        >
                                            <x-vdbs.icon name="eye" size="17" />
                                        </a>

                                        @if ($canManagePersons)
                                            <a
                                                class="btn btn--secondary btn--icon btn--sm"
                                                href="{{ route('administration.persons.edit', $listedPerson) }}"
                                                title="Bearbeiten"
                                                aria-label="{{ $displayName }} bearbeiten"
                                            >
                                                <x-vdbs.icon name="edit" size="17" />
                                            </a>
                                        @endif

                                        @if ($canManageUserStatus)
                                            @if ($canResetPassword)
                                                <form
                                                    method="POST"
                                                    action="{{ route('administration.persons.password-reset', $listedPerson) }}"
                                                >
                                                    @csrf
                                                    <button
                                                        class="btn btn--secondary btn--icon btn--sm"
                                                        type="submit"
                                                        title="Passwort zurücksetzen"
                                                        aria-label="Passwort für {{ $displayName }} zurücksetzen"
                                                        onclick="return confirm('Reset-Link für dieses Konto anfordern?')"
                                                    >
                                                        <x-vdbs.icon name="key" size="17" />
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    class="btn btn--secondary btn--icon btn--sm"
                                                    type="button"
                                                    title="Passwort zurücksetzen – kein aktives und bestätigtes Konto"
                                                    aria-label="Passwort zurücksetzen nicht verfügbar"
                                                    disabled
                                                >
                                                    <x-vdbs.icon name="key" size="17" />
                                                </button>
                                            @endif

                                            @if ($canLockAccount)
                                                <form
                                                    method="POST"
                                                    action="{{ route('administration.persons.account.disable', $listedPerson) }}"
                                                >
                                                    @csrf
                                                    <button
                                                        class="btn btn--danger btn--icon btn--sm"
                                                        type="submit"
                                                        title="Konto sperren"
                                                        aria-label="Konto von {{ $displayName }} sperren"
                                                        onclick="return confirm('Konto wirklich sperren? Bestehende Sitzungen werden beendet.')"
                                                    >
                                                        <x-vdbs.icon name="lock" size="17" />
                                                    </button>
                                                </form>
                                            @else
                                                <button
                                                    class="btn btn--danger btn--icon btn--sm"
                                                    type="button"
                                                    title="Konto sperren – nicht verfügbar"
                                                    aria-label="Konto sperren nicht verfügbar"
                                                    disabled
                                                >
                                                    <x-vdbs.icon name="lock" size="17" />
                                                </button>
                                            @endif
                                        @endif
                                    </div>
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
