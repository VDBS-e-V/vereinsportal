@extends('errors.layout')

@section('documentTitle', 'Zugriff nicht erlaubt')

@section('content')
    <x-vdbs.error-page
        code="403"
        title="Zugriff nicht erlaubt"
        description="Sie haben derzeit keine Berechtigung, diese Seite aufzurufen."
        support="Wechseln Sie zurück zur Übersicht oder melden Sie sich mit einem anderen Konto an, falls Sie diesen Bereich benötigen."
        variant="warning"
        primary-label="Zur Startseite"
        :primary-url="url('/')"
        secondary-label="Zur Anmeldung"
        :secondary-url="url('/anmelden')"
    />
@endsection
