@props(['variant' => 'info', 'title' => null])

@php
    $variant = in_array($variant, ['info', 'success', 'warning', 'error'], true) ? $variant : 'info';
    $icon = ['info' => 'info', 'success' => 'check', 'warning' => 'triangle-alert', 'error' => 'circle-alert'][$variant];
@endphp

<div {{ $attributes->class(['vdbs-alert', "vdbs-alert--{$variant}"]) }} role="{{ $variant === 'error' ? 'alert' : 'status' }}">
    <x-vdbs.icon :name="$icon" :size="24" />
    <div class="vdbs-alert__body">
        @if ($title) <p class="vdbs-alert__title"><strong>{{ $title }}</strong></p> @endif
        {{ $slot }}
    </div>
</div>
