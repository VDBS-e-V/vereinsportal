@extends('layouts.administration')

@section('title', 'Person anlegen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm">
                <p class="page-title__kicker">Personenverwaltung</p>
                <h1 class="page-title__title">Person anlegen</h1>
                <p class="page-title__lead">Legen Sie einen Personendatensatz an. Ein Benutzerkonto wird dabei nicht automatisch erstellt.</p>
            </div>
            <div class="page-title__actions">
                <a class="btn btn--secondary" href="{{ route('administration.persons.index') }}">Zur Personenliste</a>
            </div>
        </header>

        @if ($errors->any())
            <x-vdbs.validation-summary role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </x-vdbs.validation-summary>
        @endif

        @if ($possibleMatches->isNotEmpty())
            <section class="stack">
                <x-vdbs.notice type="warning" role="alert">Prüfen Sie die möglichen Treffer. Wenn keiner dieselbe Person ist, können Sie die Neuanlage unten bewusst fortsetzen. Ändern Sie die Identitätsdaten, wird die Dublettenprüfung erneut durchgeführt.</x-vdbs.notice>
                <div class="record-list">
                    @foreach ($possibleMatches as $possibleMatch)
                        @php
                            $possibleName = trim($possibleMatch->first_name.' '.($possibleMatch->name_addition !== null ? $possibleMatch->name_addition.' ' : '').$possibleMatch->last_name);
                        @endphp
                        <article class="record-item">
                            <div class="record-item__main">
                                <h2 class="record-item__title">
                                    <a href="{{ route('administration.persons.show', $possibleMatch) }}">{{ $possibleName }}</a>
                                </h2>
                                <div class="record-item__meta">
                                    <span>{{ $possibleMatch->email }}</span>
                                    <span>Geboren {{ $possibleMatch->birth_date->format('d.m.Y') }}</span>
                                    <span>{{ $possibleMatch->user !== null ? 'Portalzugang vorhanden' : 'Kein Portalzugang' }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <form class="form" method="POST" action="{{ route('administration.persons.store') }}">
            @csrf

            @if ($possibleMatches->isNotEmpty() && $possibleDuplicateConfirmation !== null)
                <input type="hidden" name="possible_duplicate_confirmation" value="{{ $possibleDuplicateConfirmation }}">
            @endif

            @include('administration.persons._form', [
                'person' => null,
                'submitLabel' => $possibleMatches->isNotEmpty() ? 'Trotzdem Person anlegen' : 'Person anlegen',
                'cancelUrl' => route('administration.persons.index'),
            ])
        </form>
    </div>
@endsection
