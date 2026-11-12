@props(['size' => 'm'])

{{--
    Fließtext-Stil (Paket "content"): einheitliche Typografie für redaktionelle Texte wie Impressum, Datenschutz und Artikel.
    Der Slot enthält normales HTML (h2–h4, p, ul, ol, blockquote, table, figure, code, hr, a). Die Zeilenlänge ist auf
    75 Zeichen begrenzt (WCAG 1.4.8).
    size  m | l   (l: größere Schrift für lange Lesetexte)
--}}

@php $size = $size === 'l' ? 'l' : 'm'; @endphp

<div {{ $attributes->class(['vdbs-prose', "vdbs-prose--{$size}"]) }}>{{ $slot }}</div>
