@extends('layouts.administration')

@section('title', 'Mitgliedschaft bearbeiten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Mitgliedschaften</p>
            <h1 class="page-title__title">Mitgliedschaft von {{ $displayName }} bearbeiten</h1>
            <p class="page-title__lead">Korrigieren Sie Beginn oder ein bereits geplantes Enddatum. Beendete Mitgliedschaften werden nicht wieder geöffnet.</p>
        </header>

        @if ($errors->any())
            <x-vdbs.validation-summary role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </x-vdbs.validation-summary>
        @endif

        <form class="form" method="POST" action="{{ route('administration.memberships.update', $membership) }}">
            @csrf
            @method('PUT')
            @include('administration.memberships._form', [
                'membership' => $membership,
                'submitLabel' => 'Änderungen speichern',
                'cancelUrl' => route('administration.memberships.show', $membership),
            ])
        </form>
    </div>
@endsection
