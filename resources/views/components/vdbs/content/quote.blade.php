@props(['source' => null, 'role' => null, 'cite' => null, 'tone' => 'default'])

{{--
    Zitat (Paket "content"): Hervorgehobene Aussage mit Quelle, z. B. Stimmen von Schulen oder Mitgliedern.
    source  Name, role  Funktion/Schule darunter, cite  Adresse der Quelle
    tone    default | brand   (brand: Markenfläche mit Ink-Schrift)
--}}

@php $tone = $tone === 'brand' ? 'brand' : 'default'; @endphp

<figure {{ $attributes->class(['vdbs-quote', "vdbs-quote--{$tone}"]) }}>
    <blockquote class="vdbs-quote__text" @if ($cite) cite="{{ $cite }}" @endif>{{ $slot }}</blockquote>
    @if ($source)
        <figcaption class="vdbs-quote__source"><strong>{{ $source }}</strong>@if ($role) <span>{{ $role }}</span> @endif</figcaption>
    @endif
</figure>
