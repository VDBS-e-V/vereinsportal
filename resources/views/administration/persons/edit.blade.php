@extends('layouts.administration')

@section('title', $displayName.' bearbeiten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title page-title--split">
            <div class="stack stack--sm"><p class="page-title__kicker">Personenverwaltung</p><h1 class="page-title__title">{{ $displayName }} bearbeiten</h1><p class="page-title__lead">Stammdaten der Person aktualisieren.</p></div>
            <div class="page-title__actions"><a class="btn btn--secondary" href="{{ route('administration.persons.show', $person) }}">Zur Person</a></div>
        </header>
        @if ($errors->any())<x-vdbs.validation-summary role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</x-vdbs.validation-summary>@endif
        <form class="form" method="POST" action="{{ route('administration.persons.update', $person) }}">
            @csrf
            @method('PUT')
            @include('administration.persons._form', ['person' => $person, 'submitLabel' => 'Personendaten speichern', 'cancelUrl' => route('administration.persons.show', $person)])
        </form>
    </div>
@endsection
