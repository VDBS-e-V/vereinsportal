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
        :image="asset('images/fehlerseiten/wartungsarbeiten.webp')"
        image-alt="Illustration: Zwei Studierende reparieren gemeinsam ein großes Zahnrad, im Hintergrund zeigt eine Tafel einen Fortschrittsbalken."
        :links="\App\Support\ErrorPageLinks::for('503')"
    />
@endsection
