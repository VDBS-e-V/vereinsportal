@props(['errors' => null, 'title' => 'Bitte prüfen Sie Ihre Eingaben', 'focus' => true])

{{--
    Fehlerübersicht über dem Formular (Paket "forms"), Styleguide/WCAG: eine Liste aller Fehler mit Sprungmarken
    zu den Feldern. role="alert" und Fokus beim Laden, damit Screenreader sie sofort vorlesen.

    errors  MessageBag/ViewErrorBag oder Array [feldname => meldung]; ohne Angabe der Validierungsfehler der Seite.
    Die Sprungmarken folgen der Id-Konvention field-<name> (FieldState::idFor).
--}}

@php
    $source = $errors ?? ($__env->shared('errors') ?? null);

    if ($source instanceof \Illuminate\Support\ViewErrorBag) {
        $source = $source->getBag('default');
    }

    $messages = [];

    if (is_array($source)) {
        $messages = $source;
    } elseif (is_object($source) && method_exists($source, 'getMessages')) {
        $messages = collect($source->getMessages())->map(fn ($m) => $m[0] ?? null)->filter()->all();
    } elseif (is_object($source) && method_exists($source, 'toArray')) {
        $messages = collect($source->toArray())->map(fn ($m) => is_array($m) ? ($m[0] ?? null) : $m)->filter()->all();
    }
@endphp

@if ($messages !== [])
    <div {{ $attributes->class('vdbs-error-summary') }} role="alert" tabindex="-1" aria-labelledby="error-summary-title" @if ($focus) data-vdbs-error-summary @endif>
        <h2 class="vdbs-error-summary__title" id="error-summary-title"><x-vdbs.icon name="circle-alert" :size="20" /> {{ $title }}</h2>
        <ul class="vdbs-error-summary__list">
            @foreach ($messages as $field => $message)
                <li><a href="#{{ \App\Support\Forms\FieldState::idFor((string) $field) }}">{{ $message }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
