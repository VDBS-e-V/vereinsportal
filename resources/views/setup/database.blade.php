@extends('setup.layout')
@section('title', 'Datenbank')
@section('step', 'database')
@section('content')
    <h2>3. Datenbank anlegen</h2>
    @if ($result === null)
        <p>Dieser Schritt legt alle Tabellen an und trägt die Rollen ein. Er löscht nichts und lässt sich wiederholen. Legen Sie vorher eine Sicherung an, wenn die Datenbank schon Daten enthält.</p>
        <form method="post" action="{{ route('setup.handle') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="step" value="database">
            <input type="hidden" name="run" value="1">
            <button type="submit">Datenbank jetzt anlegen</button>
        </form>
    @else
        <p class="box {{ $result['ok'] ? '' : 'error' }}"><span class="{{ $result['ok'] ? 'ok' : 'error' }}">{{ $result['ok'] ? 'Die Datenbank ist auf dem Stand.' : 'Es gab einen Fehler.' }}</span></p>
        <pre>{{ $result['output'] }}</pre>
        <form method="post" action="{{ route('setup.handle') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            @if ($result['ok'])
                <input type="hidden" name="step" value="admin">
                <button type="submit">Weiter zur Administration</button>
            @else
                <input type="hidden" name="step" value="database">
                <input type="hidden" name="run" value="1">
                <button type="submit">Erneut versuchen</button>
            @endif
        </form>
    @endif
@endsection
