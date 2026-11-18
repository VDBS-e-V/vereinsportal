<x-layouts.app title="Vorstand" width="full">
    <x-vdbs.layout.area-hero :area="\App\Support\PortalAreas::Vorstand" title="Übersicht für den Vorstand" :greeting="$greeting" lead="Kennzahlen zum Verein. Es werden nur Zahlen gezeigt, keine Personendaten.">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('membership.index')" icon="users">Mitgliedschaften</x-vdbs.actions.button>
            @can('board.meetings')
                <x-vdbs.actions.button :href="route('board.meetings.index')" variant="secondary" icon="calendar">Vorstandssitzungen</x-vdbs.actions.button>
            @endcan
        </x-slot:actions>
        @can('board.meetings')
            <x-slot:aside>
                <p class="vdbs-area-hero__aside-label">Nächste Sitzung</p>
                <p class="vdbs-area-hero__aside-value">{{ $stats['nextMeeting'] ?? 'keine geplant' }}</p>
                <a href="{{ route('board.meetings.index') }}">Zu den Vorstandssitzungen</a>
            </x-slot:aside>
        @endcan
    </x-vdbs.layout.area-hero>

    <x-vdbs.layout.container>
        <div class="vdbs-area-sections">
            @include('portal._tasks', ['area' => \App\Support\PortalAreas::Vorstand])

            <section aria-labelledby="board-members">
                <h2 id="board-members" style="font-size:var(--font-size-h3)">Mitgliedschaften</h2>
                <x-vdbs.layout.grid :columns="4">
                    <x-vdbs.data.stat label="Aktive Mitglieder" :value="$stats['members']" tone="success" :href="route('membership.index')" />
                    <x-vdbs.data.stat label="Anträge ohne Entscheidung" :value="$stats['requested']" :tone="$stats['requested'] > 0 ? 'warning' : 'neutral'" :href="route('membership.index')" />
                    <x-vdbs.data.stat label="Geplante Austritte" :value="$stats['leaving']" hint="Mitglieder mit eingetragenem Austrittsdatum" />
                    @can('access_requests.view')
                        <x-vdbs.data.stat label="Neue Zugangsanträge" :value="$stats['accessRequests']" :tone="$stats['accessRequests'] > 0 ? 'warning' : 'neutral'" :href="route('access-requests.index')" />
                    @endcan
                </x-vdbs.layout.grid>
            </section>

            @can('board.meetings')
                <section aria-labelledby="board-work">
                    <h2 id="board-work" style="font-size:var(--font-size-h3)">Vorstandsarbeit</h2>
                    <x-vdbs.layout.grid :columns="4">
                        <x-vdbs.data.stat label="Nächste Sitzung" :value="$stats['nextMeeting'] ?? 'keine geplant'" :href="route('board.meetings.index')" />
                        <x-vdbs.data.stat label="Offene Aufgaben" :value="$stats['boardTasksOpen']" :href="$stats['boardProject'] ? route('tasks.projects.show', $stats['boardProject']) : route('tasks.index')" />
                        <x-vdbs.data.stat label="Überfällige Aufgaben" :value="$stats['boardTasksOverdue']" :tone="$stats['boardTasksOverdue'] > 0 ? 'warning' : 'neutral'" :href="$stats['boardProject'] ? route('tasks.projects.show', [$stats['boardProject'], 'view' => 'list', 'overdue' => 1]) : route('tasks.index')" />
                    </x-vdbs.layout.grid>
                </section>
            @endcan

            <section aria-labelledby="board-team">
                <h2 id="board-team" style="font-size:var(--font-size-h3)">Teamende</h2>
                <x-vdbs.layout.grid :columns="4">
                    <x-vdbs.data.stat label="Teamende" :value="$stats['team']" />
                    <x-vdbs.data.stat label="Einarbeitung offen" :value="$stats['onboarding']" :tone="$stats['onboarding'] > 0 ? 'warning' : 'neutral'" :href="route('team-onboarding.index')" />
                </x-vdbs.layout.grid>
            </section>

            @include('portal._area-pages', ['area' => \App\Support\PortalAreas::Vorstand, 'cards' => $cards])
        </div>
    </x-vdbs.layout.container>
</x-layouts.app>
