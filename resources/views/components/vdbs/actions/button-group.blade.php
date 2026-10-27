@props(['label' => null, 'align' => 'start', 'stack' => true])

{{-- Gruppe von Buttons (Paket "actions"). align: start | center | end | between. stack: auf dem Handy untereinander. --}}

@php
    $align = in_array($align, ['start', 'center', 'end', 'between'], true) ? $align : 'start';
@endphp

<div {{ $attributes->class(['vdbs-btn-group', "vdbs-btn-group--{$align}", 'vdbs-btn-group--stack' => $stack]) }} role="group" @if ($label) aria-label="{{ $label }}" @endif>{{ $slot }}</div>
