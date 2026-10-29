@props(['name', 'src' => null, 'size' => 'm'])

{{--
    Avatar (Paket "data"): Foto oder Initialen in einem runden Kreis.
    size  s | m | l
    Der Name steht als Text für Screenreader (visuell versteckt), wenn kein umgebender Text ihn nennt:
    mit dem Attribut aria-hidden="true" am Avatar lässt sich das abschalten.
--}}

@php
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = $parts === [] ? '?' : mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
@endphp

<span {{ $attributes->class(['vdbs-avatar', "vdbs-avatar--{$size}"]) }}>
    @if ($src)
        <img class="vdbs-avatar__img" src="{{ $src }}" alt="">
    @else
        <span class="vdbs-avatar__initials" aria-hidden="true">{{ $initials }}</span>
    @endif
    <span class="vdbs-sr-only">{{ $name }}</span>
</span>
