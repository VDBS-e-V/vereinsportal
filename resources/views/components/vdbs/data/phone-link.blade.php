@props(['number', 'label' => null])

{{--
    Telefonnummer zum Anklicken (Paket "data"): öffnet ein kleines Menü mit „Anrufen“ (tel:-Link) und „Nummer kopieren“.
    Ohne JavaScript bleibt „Anrufen“ nutzbar, „Kopieren“ erscheint nur mit JavaScript.
--}}

@php $dial = preg_replace('/[^0-9+]/', '', (string) $number); @endphp

<x-vdbs.navigation.dropdown :label="$number" variant="plain" class="vdbs-phone-link">
    <x-vdbs.navigation.dropdown-item :href="'tel:'.$dial" icon="phone">Anrufen</x-vdbs.navigation.dropdown-item>
    <x-vdbs.navigation.dropdown-item class="vdbs-js-only" icon="copy" :data-vdbs-copy="$number">Nummer kopieren</x-vdbs.navigation.dropdown-item>
</x-vdbs.navigation.dropdown>
