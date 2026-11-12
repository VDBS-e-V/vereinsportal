@props(['label', 'align' => 'start', 'variant' => 'default'])

{{--
    Aufklappmenü (Paket "navigation"): Knopf mit einer Liste von Links und Aktionen.
    Gebaut auf <details>: funktioniert ohne JavaScript; mit JavaScript schließen Esc und ein Klick daneben das Menü.
    label    Text im Knopf; per Slot "trigger" lässt sich stattdessen z. B. ein Avatar mit Namen einsetzen
    align    start | end   Kante, an der das Menü ausgerichtet ist
    variant  default | plain   plain: Knopf ohne Rahmen (im Kopfbereich)
    Standard-Slot: <x-vdbs.navigation.dropdown-item> oder eigene Kinder (z. B. ein Formular mit Button)
--}}

@php
    $align = $align === 'end' ? 'end' : 'start';
    $variant = $variant === 'plain' ? 'plain' : 'default';
@endphp

<details {{ $attributes->class(['vdbs-dropdown', "vdbs-dropdown--{$align}", "vdbs-dropdown--{$variant}"]) }} data-vdbs-dropdown>
    <summary class="vdbs-dropdown__trigger">
        @isset($trigger)
            {{ $trigger }}
        @else
            <span>{{ $label }}</span>
        @endisset
        <x-vdbs.icon class="vdbs-dropdown__chevron" name="chevron-down" :size="18" />
    </summary>
    <div class="vdbs-dropdown__menu">{{ $slot }}</div>
</details>
