<x-account.shell title="Rollen & Bereiche" current="roles" lead="Ihre Rollen im Portal und die Bereiche, die Ihnen offenstehen. Änderungen laufen über den Vorstand oder die Geschäftsstelle. Die Mitgliedschaft im Verein finden Sie im Bereich Mitglieder.">
    <section aria-labelledby="roles-heading">
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
