<p>Zentral definiert in <code>App\Support\PortalAreas</code>.</p>
<ul style="list-style:none;padding:0;display:grid;gap:var(--space-2)">
    @foreach (\App\Support\PortalAreas::cases() as $area)
        <li style="display:flex;align-items:center;gap:var(--space-3)">
            @if ($area->groupMark()) <x-vdbs.group-mark :number="$area->groupMark()" /> @else <span style="width:2rem"></span> @endif
            <x-vdbs.icon :name="$area->icon()" /> {{ $area->label() }}
        </li>
    @endforeach
</ul>
