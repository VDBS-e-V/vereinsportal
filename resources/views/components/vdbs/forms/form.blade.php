@props(['action' => null, 'method' => 'post', 'files' => false, 'summary' => true, 'summaryTitle' => 'Bitte prüfen Sie Ihre Eingaben', 'novalidate' => true])

{{--
    Formular (Paket "forms"): setzt CSRF und Methoden-Tarnung (put/patch/delete), schaltet die Browser-Validierung
    ab (die Meldungen kommen vom Server), zeigt die Fehlerübersicht und gruppiert die Aktionen am Ende.

    Slots:  Standard (Felder), actions (Buttons, werden in einer Button-Gruppe ausgegeben)
    summary = false blendet die automatische Fehlerübersicht aus.
--}}

@php
    $method = strtolower($method);
    $httpMethod = $method === 'get' ? 'get' : 'post';
    $spoof = in_array($method, ['put', 'patch', 'delete'], true) ? strtoupper($method) : null;
@endphp

<form {{ $attributes->class('vdbs-form') }} method="{{ $httpMethod }}" @if ($action) action="{{ $action }}" @endif @if ($files) enctype="multipart/form-data" @endif @if ($novalidate) novalidate @endif>
    @if ($httpMethod === 'post')
        @csrf
        @if ($spoof) @method($spoof) @endif
    @endif

    @if ($summary)
        <x-vdbs.forms.error-summary :title="$summaryTitle" />
    @endif

    {{ $slot }}

    @isset($actions)
        <x-vdbs.actions.button-group class="vdbs-form__actions">{{ $actions }}</x-vdbs.actions.button-group>
    @endisset
</form>
