<p style="display:flex;flex-wrap:wrap;gap:var(--space-2)">
    <x-vdbs.data.badge>Neutral</x-vdbs.data.badge>
    <x-vdbs.data.badge tone="success">Erfolg</x-vdbs.data.badge>
    <x-vdbs.data.badge tone="warning">Warnung</x-vdbs.data.badge>
    <x-vdbs.data.badge tone="error">Fehler</x-vdbs.data.badge>
    @foreach (range(1, 6) as $n)
        <x-vdbs.data.badge :category="$n">Kategorie {{ $n }}</x-vdbs.data.badge>
    @endforeach
</p>
