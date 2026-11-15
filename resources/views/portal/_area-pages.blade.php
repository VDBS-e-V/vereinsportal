@if (! empty($cards))
    <section aria-labelledby="area-pages-{{ $area->value }}">
        <h2 id="area-pages-{{ $area->value }}" style="font-size:var(--font-size-h3)">Alle Seiten im Bereich</h2>
        <x-vdbs.layout.grid :columns="3">
            @foreach ($cards as $card)
                <x-vdbs.cards.link-card :title="$card['title']" :href="$card['href']" :text="$card['text']" :icon="$card['icon']" :tone="$area->groupMark()" />
            @endforeach
        </x-vdbs.layout.grid>
    </section>
@endif
