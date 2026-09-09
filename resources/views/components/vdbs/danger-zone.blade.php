@props([
    'title',
    'description' => null,
])

<section
    {{ $attributes->class([
        'danger-zone',
        'vdbs-danger-zone',
    ]) }}
>
    <div class="danger-zone__content vdbs-danger-zone__content">
        <h3 class="danger-zone__title vdbs-danger-zone__title">
            {{ $title }}
        </h3>

        @if ($description !== null)
            <p class="danger-zone__description vdbs-danger-zone__description">
                {{ $description }}
            </p>
        @endif

        @if (trim((string) $slot) !== '')
            <div class="danger-zone__body vdbs-danger-zone__body">
                {{ $slot }}
            </div>
        @endif
    </div>

    @isset($actions)
        <div class="danger-zone__actions vdbs-danger-zone__actions">
            {{ $actions }}
        </div>
    @endisset
</section>
