@props(['title', 'href', 'date', 'time' => null, 'location' => null, 'text' => null, 'level' => 3])

{{--
    Termin-Karte (Paket "content"): Datum als Kachel, Titel, Zeit und Ort. Der Titel ist der einzige Link.
    date  Datum (Y-m-d oder Carbon); time Zeit als Text (z. B. "18:00 Uhr")
--}}

@php
    $day = \Illuminate\Support\Carbon::parse($date)->locale('de');
    $tag = 'h'.max(2, min(5, (int) $level));
@endphp

<article {{ $attributes->class('vdbs-event-card') }}>
    <time class="vdbs-event-card__date" datetime="{{ $day->toDateString() }}">
        <span class="vdbs-event-card__day">{{ $day->isoFormat('D') }}</span>
        <span class="vdbs-event-card__month">{{ $day->isoFormat('MMM') }}</span>
        <span class="vdbs-sr-only">{{ $day->isoFormat('dddd, D. MMMM YYYY') }}</span>
    </time>
    <div class="vdbs-event-card__body">
        <{{ $tag }} class="vdbs-event-card__title"><a class="vdbs-event-card__link" href="{{ $href }}">{{ $title }}</a></{{ $tag }}>
        @if ($time || $location)
            <p class="vdbs-event-card__meta">
                @if ($time) <span><x-vdbs.icon name="clock" :size="16" /> {{ $time }}</span> @endif
                @if ($location) <span><x-vdbs.icon name="map-pin" :size="16" /> {{ $location }}</span> @endif
            </p>
        @endif
        @if ($text) <p class="vdbs-event-card__text">{{ $text }}</p> @endif
    </div>
</article>
