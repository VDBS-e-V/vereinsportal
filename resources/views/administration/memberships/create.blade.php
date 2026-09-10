@extends('layouts.administration')

@section('title', 'Mitgliedschaft anlegen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Mitgliedschaften</p>
            <h1 class="page-title__title">Mitgliedschaft für {{ $displayName }} anlegen</h1>
            <p class="page-title__lead">Eine Wiederaufnahme wird als neuer Zeitraum gespeichert; vorhandene Historie bleibt erhalten.</p>
        </header>

        @if ($errors->any())
            <x-vdbs.validation-summary role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </x-vdbs.validation-summary>
        @endif

        <form class="form" method="POST" action="{{ route('administration.persons.memberships.store', $person) }}">
            @csrf
            @include('administration.memberships._form', [
                'membership' => null,
                'submitLabel' => 'Mitgliedschaft anlegen',
                'cancelUrl' => route('administration.persons.show', $person),
            ])
        </form>
    </div>
@endsection
