@props([
    'variant' => 'primary',
    'shape' => null,
    'size' => 'm',
    'on' => 'light',
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
    on       light | dark | brand                                   Fläche, auf der der Button steht
             (Alternativ ein Container mit .vdbs-on-dark bzw. .vdbs-on-brand)
    href     gesetzt = <a>, sonst <button>
    icon     Lucide-Name; iconPosition start | end
    label    Pflicht für reine Icon-Buttons (leerer Slot): wird aria-label und title
    loading  zeigt Spinner, setzt aria-busy und sperrt die Aktion
    full     volle Breite
    external öffnet in neuem Tab (Icon und Hinweis für Screenreader)
    pressed  true | false für Umschalter (aria-pressed)
    disabled deaktiviert (bei Links: aria-disabled, ohne href)

    Standard-Form: primary=solid, secondary=outline, tertiary=link, danger=solid, quiet=link.
    "link" wird als Alias für variant "tertiary" noch akzeptiert.
--}}

@php
    $variant = ['link' => 'tertiary'][$variant] ?? $variant;
    $variant = in_array($variant, ['primary', 'secondary', 'tertiary', 'danger', 'quiet'], true) ? $variant : 'primary';

    $defaultShape = ['primary' => 'solid', 'secondary' => 'outline', 'tertiary' => 'link', 'danger' => 'solid', 'quiet' => 'link'][$variant];
    $shape = in_array($shape, ['solid', 'outline', 'link', 'arrow', 'line'], true) ? $shape : $defaultShape;

    $size = in_array($size, ['s', 'm', 'l', 'xl'], true) ? $size : 'm';
    $on = in_array($on, ['light', 'dark', 'brand'], true) ? $on : 'light';
    $iconPosition = $iconPosition === 'end' ? 'end' : 'start';

    $iconOnly = $label !== null && $slot->isEmpty();

    if ($shape === 'arrow' && ! $icon) { $icon = 'arrow-right'; $iconPosition = 'end'; }
    if ($shape === 'line' && ! $icon) { $icon = 'chevron-right'; $iconPosition = 'start'; }
    if ($external && ! $icon) { $icon = 'external-link'; $iconPosition = 'end'; }

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
        "vdbs-btn--on-{$on}" => $on !== 'light',
        'vdbs-btn--full' => $full,
        'vdbs-btn--icon-only' => $iconOnly,
        'vdbs-btn--loading' => $loading,
        'vdbs-btn--disabled' => $inactive,
    ]) }}
>
    @if ($loading)
        <span class="vdbs-btn__spinner" aria-hidden="true"></span>
    @elseif ($icon && $iconPosition === 'start')
        <x-vdbs.icon class="vdbs-btn__icon" :name="$icon" :size="$iconSize" />
    @endif

    @unless ($iconOnly)
        <span class="vdbs-btn__label">{{ $slot }}</span>
    @endunless

    @if ($icon && $iconPosition === 'end' && ! $loading)
        @if ($shape === 'arrow')
            <span class="vdbs-btn__arrow"><x-vdbs.icon class="vdbs-btn__icon" :name="$icon" :size="$iconSize" /></span>
        @else
            <x-vdbs.icon class="vdbs-btn__icon" :name="$icon" :size="$iconSize" />
        @endif
    @endif

    @if ($external)
        <span class="vdbs-sr-only">(öffnet in neuem Tab)</span>
    @endif
</{{ $tag }}>
