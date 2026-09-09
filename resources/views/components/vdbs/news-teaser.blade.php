@props([
    'title',
    'url',
    'date',
    'category' => null,
    'description' => null,
])

<article
    {{ $attributes->class([
        'news-teaser',
        'vdbs-news-teaser',
    ]) }}
>
    <div class="news-teaser__content vdbs-news-teaser__content">
        @if ($category !== null)
            <span class="news-teaser__eyebrow vdbs-news-teaser__eyebrow">
                {{ $category }}
            </span>
        @endif

        <h3 class="news-teaser__title vdbs-news-teaser__title">
            <a href="{{ $url }}">{{ $title }}</a>
        </h3>

        @if ($description !== null)
            <p class="news-teaser__description vdbs-news-teaser__description">
                {{ $description }}
            </p>
        @endif
    </div>

    <time class="news-teaser__date vdbs-news-teaser__date">
        {{ $date }}
    </time>
</article>
