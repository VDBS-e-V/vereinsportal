@extends('errors.layout')

@section('documentTitle', 'Technischer Fehler')

@section('content')
    <x-vdbs.error-page
        code="500"
        title="Es ist ein Fehler aufgetreten"
        description="Beim Laden der Seite ist ein unerwarteter technischer Fehler aufgetreten."
        support="Versuchen Sie es erneut. Wenn der Fehler bestehen bleibt, wenden Sie sich bitte an den Support."
        variant="danger"
        primary-label="Zur Startseite"
        :primary-url="url('/')"
        secondary-label="Erneut versuchen"
        :secondary-url="request()->fullUrl()"
    />
@endsection
