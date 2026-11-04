@extends('setup.layout')
@section('title', 'Schlüssel')
@section('step', 'key')
@section('content')
    <h2>2. Anwendungsschlüssel</h2>
    @if ($has)
        <p class="box"><span class="ok">Der Schlüssel (APP_KEY) ist gesetzt.</span></p>
        <form method="post" action="{{ route('setup.handle') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="step" value="database">
            <button type="submit">Weiter zur Datenbank</button>
        </form>
    @else
        <p>In der <code>.env</code> fehlt der Anwendungsschlüssel. Er verschlüsselt Sitzungen und gespeicherte Geheimnisse. Tragen Sie diese Zeile in die <code>.env</code> ein, speichern Sie die Datei per FTP und klicken Sie auf „Erneut prüfen“:</p>
        <pre>APP_KEY={{ $newKey }}</pre>
        <p><strong>Wichtig:</strong> Bewahren Sie den Schlüssel sicher auf (er gehört zur Datensicherung). Geht er verloren, sind verschlüsselte Daten nicht mehr lesbar. Wird ein anderer Schlüssel eingetragen als der angezeigte, ist das ebenfalls in Ordnung.</p>
        <form method="post" action="{{ route('setup.handle') }}">
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="step" value="key">
            <button type="submit">Erneut prüfen</button>
        </form>
    @endif
@endsection
