<x-layouts.app title="Start">
    <h1>Willkommen im Vereinsportal</h1>
    <p>Hier entstehen nach und nach die Bereiche des VDBS-Portals.</p>

    <ul style="list-style:none;padding:0;display:grid;gap:var(--space-3);grid-template-columns:repeat(auto-fill,minmax(14rem,1fr))">
        @foreach ($areas as $area)
            <li>
                <x-vdbs.card>
                    <x-vdbs.icon :name="$area->icon()" :size="24" />
                    <strong>{{ $area->label() }}</strong>
                </x-vdbs.card>
            </li>
        @endforeach
    </ul>
</x-layouts.app>
