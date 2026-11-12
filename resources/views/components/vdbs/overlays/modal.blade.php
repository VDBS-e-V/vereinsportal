@props(['id', 'title'])

<dialog id="{{ $id }}" {{ $attributes->class('vdbs-modal') }} aria-labelledby="{{ $id }}-title" data-vdbs-dialog>
    <div class="vdbs-modal__header">
        <h2 class="vdbs-modal__title" id="{{ $id }}-title">{{ $title }}</h2>
        <button class="vdbs-modal__close" type="button" aria-label="Dialog schließen" data-vdbs-dialog-close><x-vdbs.icon name="x" /></button>
    </div>
    <div class="vdbs-modal__body">{{ $slot }}</div>
    @isset($actions)
        <div class="vdbs-modal__actions">{{ $actions }}</div>
    @endisset
</dialog>
