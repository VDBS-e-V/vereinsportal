@props(['steps', 'current' => 1, 'label' => 'Fortschritt', 'orientation' => 'horizontal'])

{{--
    Schritt-Anzeige (Paket "navigation"), z. B. für die Einrichtung der Zwei-Faktor-Anmeldung oder das Onboarding.
    steps        Liste aus Texten oder ['label' => string, 'hint' => ?string]
    current      Nummer des aktuellen Schritts (1-basiert); davor liegende gelten als erledigt
    orientation  horizontal | vertical
    Der Zustand steht auch als Text da (erledigt, aktuell, offen) und der aktuelle Schritt trägt aria-current="step".
--}}

@php
    $orientation = $orientation === 'vertical' ? 'vertical' : 'horizontal';
    $total = count($steps);
    $current = max(1, min($total, (int) $current));
@endphp

<nav {{ $attributes->class(['vdbs-stepper', "vdbs-stepper--{$orientation}"]) }} aria-label="{{ $label }}">
    <p class="vdbs-stepper__summary">Schritt {{ $current }} von {{ $total }}</p>
    <ol class="vdbs-stepper__list">
        @foreach ($steps as $index => $step)
            @php
                $number = $index + 1;
                $state = $number < $current ? 'done' : ($number === $current ? 'current' : 'todo');
                $text = is_array($step) ? $step['label'] : $step;
                $hint = is_array($step) ? ($step['hint'] ?? null) : null;
            @endphp
            <li class="vdbs-stepper__step vdbs-stepper__step--{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                <span class="vdbs-stepper__marker" aria-hidden="true">
                    @if ($state === 'done') <x-vdbs.icon name="check" :size="16" /> @else {{ $number }} @endif
                </span>
                <span class="vdbs-stepper__text">
                    <span class="vdbs-stepper__label">{{ $text }}</span>
                    @if ($hint) <span class="vdbs-stepper__hint">{{ $hint }}</span> @endif
                    <span class="vdbs-sr-only">({{ ['done' => 'erledigt', 'current' => 'aktuell', 'todo' => 'offen'][$state] }})</span>
                </span>
            </li>
        @endforeach
    </ol>
</nav>
