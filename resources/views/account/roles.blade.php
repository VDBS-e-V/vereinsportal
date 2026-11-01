<x-account.shell title="Mitgliedschaft" current="roles" lead="Ihre Mitgliedschaft, Rollen und Bereiche. Änderungen laufen über den Vorstand oder die Geschäftsstelle.">
    <section aria-labelledby="membership-heading">
        <h2 id="membership-heading" style="font-size:var(--font-size-h3)">Mitgliedschaft</h2>

        @if ($membership)
            @php $state = $membership->effectiveStatus(); @endphp
            <x-vdbs.data.detail-list layout="inline">
                <x-vdbs.data.detail-item label="Status">{{ $state->label() }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Antrag vom">{{ $membership->requested_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Mitglied seit">{{ $membership->starts_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Letzter Tag der Mitgliedschaft">{{ $membership->ends_on?->format('d.m.Y') }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
        @else
            <p>Zu Ihrem Konto ist keine Mitgliedschaft hinterlegt.</p>
        @endif
    </section>

    <section aria-labelledby="roles-heading" style="margin-top:var(--space-8)">
        <h2 id="roles-heading" style="font-size:var(--font-size-h3)">Rollen</h2>

        @php $current = $assignments->filter(fn ($assignment) => $assignment->starts_at <= now() && ($assignment->ends_at === null || $assignment->ends_at > now())); @endphp

        @if ($current->isEmpty())
            <p>Sie haben zurzeit keine Rolle.</p>
        @else
            <ul>
                @foreach ($current as $assignment)
                    <li>{{ \App\Modules\AccessGovernance\Enums\RoleKey::tryFrom($assignment->role?->key ?? '')?->label() ?? $assignment->role?->key }}, seit {{ $assignment->starts_at->format('d.m.Y') }}@if ($assignment->ends_at), bis {{ $assignment->ends_at->format('d.m.Y') }}@endif</li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="areas-heading" style="margin-top:var(--space-8)">
        <h2 id="areas-heading" style="font-size:var(--font-size-h3)">Bereiche</h2>
        @php $areas = \App\Support\PortalNavigation::visibleAreas($user); @endphp

        @if ($areas === [])
            <p>Für Sie sind keine Bereiche freigeschaltet.</p>
        @else
            <ul>
                @foreach ($areas as $area)
                    <li><a href="{{ \App\Support\PortalNavigation::home($area) }}">{{ $area->label() }}</a></li>
                @endforeach
            </ul>
        @endif
    </section>
</x-account.shell>
