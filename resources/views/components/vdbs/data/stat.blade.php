@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'neutral', 'trend' => null, 'trendLabel' => null, 'href' => null])

{{--
    Kennzahl-Kachel (Paket "data"): Zahl mit Beschriftung für Übersichten, z. B. "Aktive Nutzer 128".
    tone        neutral | success | warning | error   Farbbalken links; die Bedeutung steht zusätzlich im Hinweistext
    trend       up | down | flat   Pfeil neben trend-label (z. B. "+12 seit letztem Monat"); die Richtung steht als Text da
    href        macht die Kachel zum Link (die Beschriftung ist der Link)
    Mehrere Kacheln stehen in <x-vdbs.layout.grid :columns="4">.
--}}

@php
    $tone = in_array($tone, ['neutral', 'success', 'warning', 'error'], true) ? $tone : 'neutral';
    $trend = in_array($trend, ['up', 'down', 'flat'], true) ? $trend : null;
    $trendIcon = ['up' => 'arrow-up', 'down' => 'arrow-down', 'flat' => 'minus'][$trend] ?? null;
    $trendText = ['up' => 'gestiegen', 'down' => 'gesunken', 'flat' => 'unverändert'][$trend] ?? null;
@endphp

<div {{ $attributes->class(['vdbs-stat', "vdbs-stat--{$tone}", 'vdbs-stat--link' => $href]) }}>
    <dl class="vdbs-stat__body">
        <dt class="vdbs-stat__label">
            @if ($icon) <x-vdbs.icon :name="$icon" :size="18" /> @endif
            @if ($href) <a class="vdbs-stat__link" href="{{ $href }}">{{ $label }}</a> @else {{ $label }} @endif
        </dt>
        <dd class="vdbs-stat__value">{{ $value }}</dd>
    </dl>
    @if ($trend && $trendLabel)
        <p class="vdbs-stat__trend vdbs-stat__trend--{{ $trend }}"><x-vdbs.icon :name="$trendIcon" :size="16" /> <span class="vdbs-sr-only">{{ $trendText }}:</span> {{ $trendLabel }}</p>
    @endif
    @if ($hint) <p class="vdbs-stat__hint">{{ $hint }}</p> @endif
</div>
