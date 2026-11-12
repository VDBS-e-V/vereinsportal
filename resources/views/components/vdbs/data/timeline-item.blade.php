@props(['title', 'time' => null, 'datetime' => null, 'actor' => null, 'icon' => null, 'tone' => 'neutral'])

{{--
    Ereignis im Verlauf (Paket "data").
    time      Anzeige-Text (z. B. "28.10.2026, 14:32"), datetime das maschinenlesbare Datum
    actor     wer es ausgelöst hat; icon Lucide-Name im Marker
    tone      neutral | success | warning | error
    Der Standard-Slot nimmt eine Beschreibung auf.
--}}

@php $tone = in_array($tone, ['neutral', 'success', 'warning', 'error'], true) ? $tone : 'neutral'; @endphp

<li {{ $attributes->class(['vdbs-timeline-item', "vdbs-timeline-item--{$tone}"]) }}>
    <span class="vdbs-timeline-item__marker" aria-hidden="true">@if ($icon) <x-vdbs.icon :name="$icon" :size="16" /> @endif</span>
    <div class="vdbs-timeline-item__content">
        <p class="vdbs-timeline-item__title">{{ $title }}</p>
        @if ($time || $actor)
            <p class="vdbs-timeline-item__meta">
                @if ($time) <time{!! $datetime ? ' datetime="'.e($datetime).'"' : '' !!}>{{ $time }}</time> @endif
                @if ($time && $actor) · @endif
                @if ($actor) {{ $actor }} @endif
            </p>
        @endif
        @if (! $slot->isEmpty()) <div class="vdbs-timeline-item__text">{{ $slot }}</div> @endif
    </div>
</li>
