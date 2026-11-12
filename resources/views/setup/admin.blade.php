@extends('setup.layout')
@section('title', 'Erste Administration')
@section('step', 'admin')
@section('content')
    <h2>4. Erste Administration</h2>
    @if ($exists || $created)
        <p class="box"><span class="ok">{{ $created ? 'Das Konto wurde angelegt.' : 'Es gibt bereits eine Administration.' }}</span> Beim ersten Anmelden richten Sie die Zwei-Faktor-Anmeldung ein (Pflicht für diese Rolle).</p>
        <form method="post" action="{{ route('setup.handle') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="step" value="finish">
            <button type="submit">Weiter zum Abschluss</button>
        </form>
    @else
        <p>Dieses Konto verwaltet das Portal (Systemsteuerung, Anbindungen, Rollenvergabe). Weitere Konten legen Sie danach im Portal an.</p>
        @if ($errors !== [])
            <div class="box error" role="alert"><strong>Bitte prüfen Sie die Eingaben:</strong><ul>@foreach ($errors as $message)<li>{{ $message }}</li>@endforeach</ul></div>
        @endif
        <form method="post" action="{{ route('setup.handle') }}" autocomplete="off">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="step" value="admin">
            <input type="hidden" name="create" value="1">
            <label for="first_name">Vorname</label>
            <input id="first_name" type="text" name="first_name" value="{{ $input['first_name'] ?? '' }}" required autocomplete="off">
            <label for="last_name">Nachname</label>
            <input id="last_name" type="text" name="last_name" value="{{ $input['last_name'] ?? '' }}" required autocomplete="off">
            <label for="email">E-Mail-Adresse (Anmeldename)</label>
            <input id="email" type="email" name="email" value="{{ $input['email'] ?? '' }}" required autocomplete="off">
            <label for="birth_date">Geburtsdatum</label>
            <input id="birth_date" type="date" name="birth_date" value="{{ $input['birth_date'] ?? '' }}" required>
            <label for="password">Passwort (mindestens 12 Zeichen, Groß- und Kleinbuchstaben, Ziffer)</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <label for="password_confirmation">Passwort wiederholen</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            <button type="submit">Konto anlegen</button>
        </form>
    @endif
@endsection
