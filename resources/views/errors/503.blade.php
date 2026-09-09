@extends('errors.layout')

@section('documentTitle', 'Vorübergehend nicht verfügbar')

@section('content')
    <x-vdbs.error-page
        code="503"
        title="Vorübergehend nicht verfügbar"
        description="Dieser Bereich ist aktuell vorübergehend nicht verfügbar."
        support="Bitte versuchen Sie es in einigen Minuten erneut."
        variant="warning"
        primary-label="Erneut versuchen"
        :primary-url="request()->fullUrl()"
        secondary-label="Zur Startseite"
        :secondary-url="url('/')"
    />
@endsection
