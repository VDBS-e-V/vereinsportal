@props(['id', 'label', 'level' => 3])

{{-- Ein Tab-Inhalt (Paket "navigation"), Kind von <x-vdbs.navigation.tabs>. --}}

@php $tag = 'h'.max(2, min(5, (int) $level)); @endphp

<section {{ $attributes->class('vdbs-tab') }} id="{{ $id }}" data-vdbs-tab data-label="{{ $label }}">
    <{{ $tag }} class="vdbs-tab__title">{{ $label }}</{{ $tag }}>
    <div class="vdbs-tab__body">{{ $slot }}</div>
</section>
