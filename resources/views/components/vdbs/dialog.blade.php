@props([
    'id',
    'title',
    'description' => null,
    'danger' => false,
])

@php
    $titleId = $id.'-title';
    $descriptionId = $id.'-description';
@endphp

<dialog
    id="{{ $id }}"
    {{ $attributes->class([
        'dialog',
        'vdbs-dialog',
        'dialog--danger' => $danger,
        'vdbs-dialog--danger' => $danger,
    ]) }}
    aria-labelledby="{{ $titleId }}"
    @if ($description !== null)
        aria-describedby="{{ $descriptionId }}"
    @endif
    data-vdbs-dialog
>
    <div class="dialog__header vdbs-dialog__header">
        <div class="dialog__heading vdbs-dialog__heading">
            <h2
                class="dialog__title vdbs-dialog__title"
                id="{{ $titleId }}"
            >
                {{ $title }}
            </h2>

            @if ($description !== null)
                <p
                    class="dialog__description vdbs-dialog__description"
                    id="{{ $descriptionId }}"
                >
                    {{ $description }}
                </p>
            @endif
        </div>

        <button
            class="dialog__close vdbs-dialog__close"
            type="button"
            aria-label="Dialog schließen"
            data-vdbs-dialog-close
        >
            ×
        </button>
    </div>

    <div class="dialog__body vdbs-dialog__body">
        {{ $slot }}
    </div>

    @isset($actions)
        <div class="dialog__actions vdbs-dialog__actions">
            {{ $actions }}
        </div>
    @endisset
</dialog>
