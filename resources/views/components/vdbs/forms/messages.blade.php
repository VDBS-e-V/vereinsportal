@props(['state'])

{{-- Fehler- oder Erfolgsmeldung eines Feldes (Paket "forms", intern). Icon und Text, nie nur Farbe. --}}

@if ($state->hasError())
    <p class="vdbs-field__message vdbs-field__message--error" id="{{ $state->errorId() }}">
        <x-vdbs.icon name="circle-alert" :size="16" /><span><span class="vdbs-sr-only">Fehler: </span>{{ $state->error }}</span>
    </p>
@elseif ($state->hasSuccess())
    <p class="vdbs-field__message vdbs-field__message--success" id="{{ $state->successId() }}">
        <x-vdbs.icon name="check" :size="16" /><span>{{ $state->success }}</span>
    </p>
@endif
