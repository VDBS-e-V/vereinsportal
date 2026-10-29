@props(['text', 'placement' => 'top', 'id' => null])

{{--
    Tooltip (Paket "feedback"): kurze Zusatzerklärung zu einem bedienbaren Element, z. B. einem Icon-Button.
    Der Slot enthält das Element (Button oder Link; muss per Tastatur erreichbar sein). Der Hinweis erscheint bei Hover und
    Fokus; Esc schließt ihn. Mit JavaScript wird das Element zusätzlich per aria-describedby mit dem Text verbunden.
    Keine wichtige Information nur im Tooltip verstecken: auf Touch-Geräten gibt es kein Hover.
    placement  top | bottom | start | end
--}}

@php
    $placement = in_array($placement, ['top', 'bottom', 'start', 'end'], true) ? $placement : 'top';
    $id ??= 'tooltip-'.\Illuminate\Support\Str::slug($text).'-'.substr(md5($text), 0, 4);
@endphp

<span {{ $attributes->class(['vdbs-tooltip', "vdbs-tooltip--{$placement}"]) }} data-vdbs-tooltip>
    {{ $slot }}
    <span class="vdbs-tooltip__text" role="tooltip" id="{{ $id }}">{{ $text }}</span>
</span>
