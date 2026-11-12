@extends('setup.layout')
@section('title', 'Abschluss')
@section('step', 'finish')
@section('content')
    <h2>5. Abschluss</h2>
    <p class="box {{ $keys['ok'] ? '' : 'error' }}">{{ $keys['message'] }}</p>
    @if ($locked)
        <p class="box"><span class="ok">Die Einrichtung ist abgeschlossen und gesperrt.</span> Diese Adresse antwortet ab jetzt nicht mehr.</p>
        <p>Noch zu tun:</p>
        <ol>
            <li>Entfernen Sie <code>SETUP_TOKEN</code> aus der <code>.env</code>.</li>
            <li>Richten Sie beim externen Cron-Dienst (z. B. cron-job.org) den Aufruf <code>{{ url('/cron/') }}/&lt;CRON_KEY&gt;</code> jede Minute ein.</li>
            <li>Melden Sie sich im Portal an und richten Sie die Zwei-Faktor-Anmeldung ein. Prüfen Sie danach die Systemsteuerung (Admins › System).</li>
            <li>Sichern Sie <code>.env</code> und die Dateien <code>storage/oauth-*.key</code> an einem sicheren Ort.</li>
        </ol>
        <p><a href="{{ url('/') }}">Zum Portal</a></p>
    @elseif (! $hasAdmin)
        <p class="box error">Es gibt noch keine Administration. Bitte gehen Sie zurück und legen Sie das Konto an.</p>
    @else
        <p class="box error">Die Sperrdatei <code>storage/app/setup.lock</code> konnte nicht geschrieben werden. Prüfen Sie die Schreibrechte von <code>storage/app</code>. Solange sie fehlt, bleibt die Einrichtung geöffnet: Entfernen Sie <code>SETUP_TOKEN</code> aus der <code>.env</code>, um sie zu schließen.</p>
    @endif
@endsection
