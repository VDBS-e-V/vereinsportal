@props([
    'title' => 'Bitte prüfen Sie Ihre Eingaben',
    'description' => null,
    'role' => null,
])

<section
    {{ $attributes->class([
        'validation-summary',
        'vdbs-validation-summary',
    ]) }}
    tabindex="-1"
    @if ($role !== null)
        role="{{ $role }}"
    @endif
>
    <h2 class="validation-summary__title vdbs-validation-summary__title">
        {{ $title }}
    </h2>

    @if ($description !== null)
        <p class="validation-summary__description vdbs-validation-summary__description">
            {{ $description }}
        </p>
    @endif

    <ul class="validation-summary__list vdbs-validation-summary__list">
        {{ $slot }}
    </ul>
</section>
