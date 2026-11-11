@props(['action' => null, 'method' => 'post', 'files' => false, 'summary' => true, 'summaryTitle' => 'Bitte prüfen Sie Ihre Eingaben', 'novalidate' => true, 'validate' => true])

{{--
    Formular (Paket "forms"): setzt CSRF und Methoden-Tarnung (put/patch/delete), schaltet die Browser-Validierung
    ab (die Meldungen kommen vom Server), zeigt die Fehlerübersicht und gruppiert die Aktionen am Ende.

    Slots:  Standard (Felder), actions (Buttons, werden in einer Button-Gruppe ausgegeben)
    summary = false blendet die automatische Fehlerübersicht aus.
    validate  true (Standard): prüft die Felder mit JavaScript beim Verlassen und beim Absenden (die Meldungen des Servers bleiben maßgeblich).
    Enthält das Formular Pflichtfelder, steht am Anfang der Hinweis "* Pflichtfeld".
--}}

@php
    $method = strtolower($method);
    $httpMethod = $method === 'get' ? 'get' : 'post';
    $spoof = in_array($method, ['put', 'patch', 'delete'], true) ? strtoupper($method) : null;
@endphp

<form {{ $attributes->class('vdbs-form') }} method="{{ $httpMethod }}" @if ($action) action="{{ $action }}" @endif @if ($files) enctype="multipart/form-data" @endif @if ($novalidate) novalidate @endif @if ($validate) data-vdbs-validate @endif>
    @if ($httpMethod === 'post')
        @csrf
        @if ($spoof) @method($spoof) @endif
    @endif

    @if ($summary)
        <x-vdbs.forms.error-summary :title="$summaryTitle" />
    @endif

    @if (\App\Support\Forms\FieldState::pullRequiredSeen())
        <p class="vdbs-form__required-note"><span class="vdbs-field__flag vdbs-field__flag--required" aria-hidden="true">*</span> Pflichtfeld</p>
    @endif

    {{ $slot }}

    @isset($actions)
        <x-vdbs.actions.button-group class="vdbs-form__actions">{{ $actions }}</x-vdbs.actions.button-group>
    @endisset
</form>
