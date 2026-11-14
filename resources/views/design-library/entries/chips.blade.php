<p>Paket <strong>Daten</strong> · <code>&lt;x-vdbs.data.chip-list&gt;</code> mit <code>&lt;x-vdbs.data.chip&gt;</code></p>
<x-vdbs.data.chip-list label="Bereiche">
    @foreach (\App\Support\PortalAreas::withGroupMark() as $area)
        <x-vdbs.data.chip :label="$area->label()" href="#" :group="$area->groupMark()" />
    @endforeach
</x-vdbs.data.chip-list>

<p style="margin-top:var(--space-5)">Mit <code>spread</code> verteilen sich die Chips gleichmäßig auf ein Raster (2, 4 oder 5 Spalten), sodass keine einzelne Zeile übrig bleibt, z. B. auf der Startseite.</p>
<x-vdbs.data.chip-list label="Bereiche, gleichmäßig verteilt" spread>
    @foreach (\App\Support\PortalAreas::withGroupMark() as $area)
        <x-vdbs.data.chip :label="$area->label()" href="#" :group="$area->groupMark()" />
    @endforeach
</x-vdbs.data.chip-list>
