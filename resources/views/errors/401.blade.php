@extends('errors.layout')

@section('documentTitle', 'Anmeldung erforderlich')

@section('content')
    <x-vdbs.error-page
        code="401"
        title="Anmeldung erforderlich"
        description="Für diese Seite müssen Sie angemeldet sein."
        support="Melden Sie sich an, um fortzufahren."
        variant="warning"
        primary-label="Zur Anmeldung"
        :primary-url="\Illuminate\Support\Facades\Route::has('my.login') ? route('my.login') : url('/')"
        secondary-label="Zur Startseite"
        :secondary-url="url('/')"
        :image="asset('images/fehlerseiten/401.webp')"
        image-alt="Illustration: Ein Student hält sein Smartphone an ein Türschloss mit der Aufschrift 401, die Tür bleibt verschlossen."
        :links="\App\Support\ErrorPageLinks::for('401')"
    />
@endsection
