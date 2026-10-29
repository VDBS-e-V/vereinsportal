@props(['variant' => 'info', 'title' => null, 'timeout' => null, 'dismissible' => true])

{{--
    Toast (Paket "feedback"): kurze Rückmeldung nach einer Aktion, z. B. "Gespeichert". Gehört in <x-vdbs.feedback.toast-region>.
    variant      info | success | warning | error
    timeout      Sekunden bis zum automatischen Schließen (nur mit JavaScript; pausiert bei Hover und Fokus).
                 Standard: success 8, info 10, warning und error bleiben stehen
    dismissible  Schließen-Knopf (nur mit JavaScript sichtbar)
    Fehler (error) werden sofort vorgelesen (role="alert"), alle anderen höflich (role="status").
--}}

@php
    $variant = in_array($variant, ['info', 'success', 'warning', 'error'], true) ? $variant : 'info';
    $icon = ['info' => 'info', 'success' => 'check', 'warning' => 'triangle-alert', 'error' => 'circle-alert'][$variant];
    $timeout ??= ['success' => 8, 'info' => 10][$variant] ?? 0;
@endphp

<div {{ $attributes->class(['vdbs-toast', "vdbs-toast--{$variant}"]) }} role="{{ $variant === 'error' ? 'alert' : 'status' }}" data-vdbs-toast @if ((int) $timeout > 0) data-timeout="{{ (int) $timeout }}" @endif>
    <x-vdbs.icon class="vdbs-toast__icon" :name="$icon" :size="22" />
    <div class="vdbs-toast__body">
        @if ($title) <p class="vdbs-toast__title">{{ $title }}</p> @endif
        <div class="vdbs-toast__text">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="vdbs-toast__close vdbs-js-only" data-vdbs-toast-close aria-label="Meldung schließen"><x-vdbs.icon name="x" :size="18" /></button>
    @endif
</div>
