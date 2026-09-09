@props([
    'title',
    'url',
    'description' => null,
    'meta' => null,
])

<article
    {{ $attributes->class([
        'resource-item',
        'vdbs-resource-item',
    ]) }}
>
    <div class="resource-item__content vdbs-resource-item__content">
        <h3 class="resource-item__title vdbs-resource-item__title">
            <a href="{{ $url }}">{{ $title }}</a>
        </h3>

        @if ($description !== null)
            <p class="resource-item__description vdbs-resource-item__description">
                {{ $description }}
            </p>
        @endif

        @if ($meta !== null)
            <span class="resource-item__meta vdbs-resource-item__meta">
                {{ $meta }}
            </span>
        @endif
    </div>

    @isset($action)
        <div class="resource-item__action vdbs-resource-item__action">
            {{ $action }}
        </div>
    @endisset
</article>
