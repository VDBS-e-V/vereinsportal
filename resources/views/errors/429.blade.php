@extends('errors.layout')

@section('documentTitle', 'Zu viele Anfragen')

@section('content')
    <x-vdbs.error-page
        code="429"
        title="Zu viele Anfragen"
        description="Es wurden in kurzer Zeit zu viele Anfragen gesendet. Zur Sicherheit wird der Zugriff kurz begrenzt."
        support="Bitte warten Sie einen Moment und versuchen Sie es dann erneut."
        variant="warning"
        primary-label="Erneut versuchen"
        :primary-url="request()->fullUrl()"
        secondary-label="Zur Startseite"
        :secondary-url="url('/')"
        :image="asset('images/fehlerseiten/429.webp')"
        image-alt="Illustration: Ein Student sitzt am Laptop, um ihn herum schweben viele Fenster und eine Uhr, dazu die Zahl 429."
        :links="\App\Support\ErrorPageLinks::for('429')"
    />
@endsection
