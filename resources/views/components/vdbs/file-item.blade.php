@props([
    'name',
    'kind' => 'Datei',
    'meta' => null,
])

<article
    {{ $attributes->class([
        'file-item',
        'vdbs-file-item',
    ]) }}
>
    <div class="file-item__main vdbs-file-item__main">
        <div class="file-item__heading vdbs-file-item__heading">
            <span class="file-item__name vdbs-file-item__name">
                {{ $name }}
            </span>
            <x-vdbs.badge>{{ $kind }}</x-vdbs.badge>
        </div>

        @if ($meta !== null)
            <span class="file-item__meta vdbs-file-item__meta">
                {{ $meta }}
            </span>
        @endif

        @isset($status)
            <div class="file-item__status vdbs-file-item__status">
                {{ $status }}
            </div>
        @endisset
    </div>

    @isset($actions)
        <div class="file-item__actions vdbs-file-item__actions">
            {{ $actions }}
        </div>
    @endisset
</article>
