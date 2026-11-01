@props([
    'variant' => 'primary',
    'tone' => 'green',
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

    variant  primary | secondary | tertiary | text | danger        Wichtigkeit der Aktion
             primary    dunkle Fläche, weiße Schrift
             secondary  helle Fläche, Ink-Schrift
             tertiary   weiße Fläche, Schrift und Rahmen in Farbe
             text       nur Schrift in Farbe (Fläche und Unterstrich erst bei Hover)
             danger     Gefahr (rot, gefüllt)
    tone     green | lila                                           Tonfarbe von primary, secondary, tertiary und text (Standard green = Aktion)
    size     s | m | l                                              36, 44, 52 px Höhe
    on       default | night | dark | brand                         Fläche, auf der der Button steht
             night = Fläche des Dunkelmodus (Graphit), Töne des Dunkelmodus; dark = Lila/Ink/Bild (Heros); brand = helles Markengrün
             (Alternativ ein Container mit .vdbs-on-night, .vdbs-on-dark bzw. .vdbs-on-brand)
    href     gesetzt = <a>, sonst <button>
    icon     Lucide-Name; iconPosition start | end: mit und ohne Text, mit und ohne Icon in jeder Rolle
    label    Pflicht für reine Icon-Buttons (leerer Slot): wird aria-label und title; Icon-Buttons sind 44 × 44 px
    loading  zeigt Spinner, setzt aria-busy und sperrt die Aktion
    full     volle Breite
    external öffnet in neuem Tab (Icon und Hinweis für Screenreader)
    pressed  true | false für Umschalter (aria-pressed)
    disabled deaktiviert (bei Links: aria-disabled, ohne href)

    Jede Rolle bringt ihre Form mit (primary, secondary und danger gefüllt, tertiary mit Rahmen, text ohne Rahmen). Weitere Formen gibt es nicht.
--}}

@php
    $variant = in_array($variant, ['primary', 'secondary', 'tertiary', 'text', 'danger'], true) ? $variant : 'primary';
    $tone = $tone === 'lila' ? 'lila' : 'green';

    $form = ['primary' => 'solid', 'secondary' => 'solid', 'tertiary' => 'outline', 'text' => 'text', 'danger' => 'solid'][$variant];

    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $on = in_array($on, ['default', 'night', 'dark', 'brand'], true) ? $on : 'default';
    $iconPosition = $iconPosition === 'end' ? 'end' : 'start';

    $iconOnly = $label !== null && $slot->isEmpty();

    if ($external && ! $icon) { $icon = 'external-link'; $iconPosition = 'end'; }

    $startIcon = $icon && $iconPosition === 'start' ? $icon : null;
    $endIcon = $icon && $iconPosition === 'end' ? $icon : null;

    $iconSize = ['s' => 16, 'm' => 18, 'l' => 20][$size];
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
        'vdbs-btn--tone-lila' => $tone === 'lila',
        "vdbs-btn--form-{$form}",
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
        <x-vdbs.icon class="vdbs-btn__icon" :name="$startIcon" :size="$iconSize" />
    @endif

    @unless ($iconOnly)
        <span class="vdbs-btn__label">{{ $slot }}</span>
    @endunless

    @if ($endIcon && ! $loading)
        <x-vdbs.icon class="vdbs-btn__icon" :name="$endIcon" :size="$iconSize" />
    @endif

    @if ($external)
        <span class="vdbs-sr-only">(öffnet in neuem Tab)</span>
    @endif
</{{ $tag }}>
