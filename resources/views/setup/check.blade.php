@extends('setup.layout')
@section('title', 'Voraussetzungen')
@section('step', 'check')
@section('content')
    <h2>1. Voraussetzungen</h2>
    <table>
        @foreach ($rows as $row)
            <tr><th scope="row">{{ $row['label'] }}</th><td class="{{ $row['status'] }}">{{ ['ok' => 'in Ordnung', 'warning' => 'Hinweis', 'error' => 'Problem'][$row['status']] }}</td><td>{{ $row['detail'] }}</td></tr>
        @endforeach
    </table>
    <form method="post" action="{{ route('setup.handle') }}">
        <input type="hidden" name="token" value="{{ $token }}">
        @if ($blocked)
            <p class="box error">Bitte beheben Sie die Probleme und laden Sie die Seite mit „Erneut prüfen“.</p>
            <input type="hidden" name="step" value="check">
            <button type="submit">Erneut prüfen</button>
        @else
            <input type="hidden" name="step" value="key">
            <button type="submit">Weiter zum Schlüssel</button>
        @endif
    </form>
@endsection
