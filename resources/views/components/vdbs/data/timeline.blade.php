@props(['label' => 'Verlauf'])

{{--
    Verlauf (Paket "data"): Ereignisse in zeitlicher Reihenfolge, z. B. Aktivitäten, Statusänderungen oder ein Compliance-Protokoll.
    Kinder: <x-vdbs.data.timeline-item>. Die Reihenfolge bestimmt der Aufrufer (meist neueste zuerst).
--}}

<ol {{ $attributes->class('vdbs-timeline') }} aria-label="{{ $label }}">{{ $slot }}</ol>
