@extends('errors.layout')

@section('documentTitle', 'Seite nicht gefunden')

@section('content')
    <x-vdbs.error-page
        code="404"
        title="Seite nicht gefunden"
        description="Die gewünschte Seite konnte nicht gefunden werden. Möglicherweise wurde sie verschoben oder die Adresse ist nicht mehr aktuell."
        support="Von hier aus gelangen Sie schnell zurück in das Portal."
        variant="info"
        primary-label="Zur Startseite"
        :primary-url="url('/')"
    />
@endsection
