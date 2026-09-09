@props([
    'day',
    'month',
    'year',
    'title',
    'url',
    'time' => null,
    'location' => null,
    'description' => null,
])

<article
    {{ $attributes->class([
        'event-teaser',
        'vdbs-event-teaser',
    ]) }}
>
    <div class="event-date vdbs-event-date" aria-hidden="true">
        <span class="event-date__day vdbs-event-date__day">{{ $day }}</span>
        <span class="event-date__month vdbs-event-date__month">{{ $month }}</span>
        <span class="event-date__year vdbs-event-date__year">{{ $year }}</span>
    </div>

    <div class="event-teaser__content vdbs-event-teaser__content">
        <h3 class="event-teaser__title vdbs-event-teaser__title">
            <a href="{{ $url }}">{{ $title }}</a>
        </h3>

        @if ($time !== null || $location !== null)
            <div class="event-teaser__meta vdbs-event-teaser__meta">
                @if ($time !== null)
                    <span>{{ $time }}</span>
                @endif
                @if ($location !== null)
                    <span>{{ $location }}</span>
                @endif
            </div>
        @endif

        @if ($description !== null)
            <p class="event-teaser__description vdbs-event-teaser__description">
                {{ $description }}
            </p>
        @endif

        <span class="sr-only">
            Termin: {{ $day }}. {{ $month }} {{ $year }}
        </span>
    </div>
</article>
