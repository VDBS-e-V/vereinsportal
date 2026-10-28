@props(['title' => 'Veranstaltungskalender', 'href' => null, 'hrefLabel' => 'Alle Termine', 'level' => 2, 'id' => null])

{{--
    Terminliste (Paket "data"), Vorbild: FU Berlin "Veranstaltungskalender".
    Kinder: <x-vdbs.data.event-item>. Slot "controls": Blätter-Knöpfe oder Zähler (z. B. "1 / 13").
    href: Link auf alle Termine (erscheint unter der Liste).
--}}

@php
    $tag = 'h'.max(2, min(4, (int) $level));
    $id ??= 'events-'.\Illuminate\Support\Str::slug($title);
@endphp

<section {{ $attributes->class('vdbs-event-list') }} aria-labelledby="{{ $id }}-title">
    <{{ $tag }} class="vdbs-event-list__title" id="{{ $id }}-title">{{ $title }}</{{ $tag }}>

    <ul class="vdbs-event-list__items">{{ $slot }}</ul>

    @if ($href || isset($controls))
        <div class="vdbs-event-list__footer">
            @isset($controls)
                <div class="vdbs-event-list__controls">{{ $controls }}</div>
            @endisset
            @if ($href)
                <a class="vdbs-event-list__all" href="{{ $href }}">{{ $hrefLabel }}</a>
            @endif
        </div>
    @endif
</section>
