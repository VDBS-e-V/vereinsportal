@props(['value' => null, 'max' => 100, 'label', 'showValue' => true])

{{--
    Fortschrittsbalken (Paket "feedback") auf Basis von <progress>.
    value  aktueller Wert; null zeigt einen unbestimmten Balken (läuft, Dauer unbekannt)
    max    Endwert (Standard 100)
    label  Beschriftung (Pflicht), z. B. "Upload"
    show-value  Prozentzahl neben dem Balken
--}}

@php
    $determinate = $value !== null;
    $percent = $determinate ? (int) round(max(0, min((float) $max, (float) $value)) / max(1, (float) $max) * 100) : null;
    $id = 'progress-'.\Illuminate\Support\Str::slug($label);
@endphp

<div {{ $attributes->class('vdbs-progress') }}>
    <label class="vdbs-progress__label" for="{{ $id }}">{{ $label }}</label>
    <progress class="vdbs-progress__bar" id="{{ $id }}" max="{{ $max }}" @if ($determinate) value="{{ $value }}" @endif>{{ $percent !== null ? $percent.' %' : 'läuft' }}</progress>
    @if ($showValue && $determinate) <span class="vdbs-progress__value">{{ $percent }} %</span> @endif
</div>
