<p style="display:flex;flex-wrap:wrap;gap:var(--space-2)">
    <x-vdbs.badge>Neutral</x-vdbs.badge>
    <x-vdbs.badge tone="success">Erfolg</x-vdbs.badge>
    <x-vdbs.badge tone="warning">Warnung</x-vdbs.badge>
    <x-vdbs.badge tone="error">Fehler</x-vdbs.badge>
    @foreach (range(1, 6) as $n)
        <x-vdbs.badge :category="$n">Kategorie {{ $n }}</x-vdbs.badge>
    @endforeach
</p>
