@extends('setup.layout')
@section('title', 'Token')
@section('content')
    <p>Diese Seite richtet das Portal ein. Geben Sie den Einmal-Token ein, den Sie in der <code>.env</code> als <code>SETUP_TOKEN</code> eingetragen haben.</p>
    @if ($error)
        <p class="box error" role="alert">{{ $error }}</p>
    @endif
    <form method="post" action="{{ route('setup.handle') }}" autocomplete="off">
        <input type="hidden" name="step" value="check">
        <label for="token">Token</label>
        <input id="token" type="password" name="token" required autocomplete="off">
        <button type="submit">Weiter</button>
    </form>
@endsection
