@props([
    'variant' => 'primary',
    'shape' => null,
    'size' => 'm',
    'on' => 'default',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconPosition' => 'start',
    'loading' => false,
    'full' => false,
    'label' => null,
    'external' => false,
    'disabled' => false,
    'pressed' => null,
])

{{--
    Das Button-System (Paket "actions"): ein Baustein für Buttons und Links im Button-Stil.

    variant  primary | secondary | tertiary | danger | quiet      Wichtigkeit der Aktion
    shape    solid | outline | link | arrow | line                  Form (Standard je Variante)
    size     s | m | l | xl
    on       default | dark | brand                                 Fläche, auf der der Button steht
             default = die normale Seitenfläche (folgt dem Theme, im Dark Mode also dunkel)
             (Alternativ ein Container mit .vdbs-on-dark bzw. .vdbs-on-brand)
    href     gesetzt = <a>, sonst <button>
    icon     Lucide-Name; iconPosition start | end — in jeder Form möglich (ein Icon vor oder nach dem Text).
             Pfeil-Form: zusätzlich immer der Pfeil am Ende. Linien-Form: ohne Icon ein Chevron am Anfang.
    label    Pflicht für reine Icon-Buttons (leerer Slot): wird aria-label und title
    loading  zeigt Spinner, setzt aria-busy und sperrt die Aktion
    full     volle Breite
    external öffnet in neuem Tab (Icon und Hinweis für Screenreader)
    pressed  true | false für Umschalter (aria-pressed)
    disabled deaktiviert (bei Links: aria-disabled, ohne href)

    Standard-Form: primary=solid, secondary=outline, tertiary=link, danger=solid, quiet=link.
--}}

@php
    $variant = in_array($variant, ['primary', 'secondary', 'tertiary', 'danger', 'quiet'], true) ? $variant : 'primary';

    $defaultShape = ['primary' => 'solid', 'secondary' => 'outline', 'tertiary' => 'link', 'danger' => 'solid', 'quiet' => 'link'][$variant];
    $shape = in_array($shape, ['solid', 'outline', 'link', 'arrow', 'line'], true) ? $shape : $defaultShape;

    $size = in_array($size, ['s', 'm', 'l', 'xl'], true) ? $size : 'm';
    $on = in_array($on, ['default', 'dark', 'brand'], true) ? $on : 'default';
    $iconPosition = $iconPosition === 'end' ? 'end' : 'start';

    $iconOnly = $label !== null && $slot->isEmpty();

    // Ein Icon vor oder nach dem Text ist in jeder Form möglich. Der Pfeil-Button hat zusätzlich
    // immer den Pfeil am Ende; der Linien-Button zeigt ohne eigenes Icon einen Chevron am Anfang.
    if ($external && ! $icon && $shape !== 'arrow') { $icon = 'external-link'; $iconPosition = 'end'; }
    if ($shape === 'line' && ! $icon) { $icon = 'chevron-right'; $iconPosition = 'start'; }

    $startIcon = $icon && $iconPosition === 'start' ? $icon : null;
    $endIcon = $icon && $iconPosition === 'end' ? $icon : null;
    $arrowCell = $shape === 'arrow';
    if ($arrowCell) { $endIcon = $endIcon ?? ($external ? 'external-link' : 'arrow-right'); }

    $iconSize = ['s' => 16, 'm' => 18, 'l' => 20, 'xl' => 24][$size];
    $inactive = $disabled || $loading;
    $tag = $href !== null ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($tag === 'a')
        @unless ($inactive) href="{{ $href }}" @endunless
        @if ($external && ! $inactive) target="_blank" rel="noopener noreferrer" @endif
        @if ($inactive) aria-disabled="true" tabindex="-1" role="link" @endif
    @else
        type="{{ $type }}"
        @if ($inactive) disabled @endif
    @endif
    @if ($loading) aria-busy="true" @endif
    @if ($pressed !== null) aria-pressed="{{ $pressed ? 'true' : 'false' }}" @endif
    @if ($iconOnly) aria-label="{{ $label }}" title="{{ $label }}" @endif
    {{ $attributes->class([
        'vdbs-btn',
        "vdbs-btn--{$variant}",
        "vdbs-btn--shape-{$shape}",
        "vdbs-btn--size-{$size}",
        "vdbs-btn--on-{$on}" => $on !== 'default',
        'vdbs-btn--full' => $full,
        'vdbs-btn--icon-only' => $iconOnly,
        'vdbs-btn--loading' => $loading,
        'vdbs-btn--disabled' => $disabled,
    ]) }}
>
    @if ($loading)
        <span class="vdbs-btn__spinner" aria-hidden="true"></span>
    @elseif ($startIcon)
        <x-vdbs.icon @class(['vdbs-btn__icon', 'vdbs-btn__icon--chevron' => $startIcon === 'chevron-right']) :name="$startIcon" :size="$iconSize" />
    @endif

    @unless ($iconOnly)
        <span class="vdbs-btn__label">{{ $slot }}</span>
    @endunless

    @if ($endIcon && ! $loading)
        @if ($arrowCell)
            <span class="vdbs-btn__arrow"><x-vdbs.icon class="vdbs-btn__icon" :name="$endIcon" :size="$iconSize" /></span>
        @else
            <x-vdbs.icon class="vdbs-btn__icon" :name="$endIcon" :size="$iconSize" />
        @endif
    @endif

    @if ($external)
        <span class="vdbs-sr-only">(öffnet in neuem Tab)</span>
    @endif
</{{ $tag }}>
