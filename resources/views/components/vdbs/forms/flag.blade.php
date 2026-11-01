@props(['state'])

{{--
    Pflichtfeld-Kennzeichnung (Paket "forms", intern): rotes Sternchen. Das Sternchen ist für Screenreader versteckt, dafür steht der Text "(Pflichtfeld)"
    nur für sie da. Optionale Felder bleiben unmarkiert; das Formular erklärt das Sternchen einmal ("* Pflichtfeld").
--}}

@if ($state->required)
    @php \App\Support\Forms\FieldState::markRequired(); @endphp
    <span class="vdbs-field__flag vdbs-field__flag--required" aria-hidden="true">*</span><span class="vdbs-sr-only"> (Pflichtfeld)</span>
@endif
