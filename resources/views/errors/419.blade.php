@extends('errors.layout')

@section('documentTitle', 'Sitzung abgelaufen')

@section('content')
    <x-vdbs.error-page
        code="419"
        title="Sitzung abgelaufen"
        description="Aus Sicherheitsgründen ist Ihre Sitzung abgelaufen. Bitte melden Sie sich erneut an."
        support="Nicht gespeicherte Eingaben müssen gegebenenfalls erneut vorgenommen werden."
        variant="warning"
        primary-label="Erneut anmelden"
        :primary-url="url('/anmelden')"
        secondary-label="Zur Startseite"
        :secondary-url="url('/')"
    />
@endsection
