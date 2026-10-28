@props(['title', 'href', 'date', 'datetime' => null, 'time' => null])

{{-- Ein Termin der Terminliste (Paket "data"): Datum und Uhrzeit links, verlinkter Titel rechts. --}}

<li class="vdbs-event-item">
    <p class="vdbs-event-item__when">
        <time{!! $datetime ? ' datetime="'.e($datetime).'"' : '' !!}>{{ $date }}</time>
        @if ($time) <span class="vdbs-event-item__time">{{ $time }}</span> @endif
    </p>
    <a class="vdbs-event-item__title" href="{{ $href }}">{{ $title }}</a>
</li>
