@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Übersicht']];
@endphp

<x-layouts.app title="Vorstand" width="wide">
    <x-vdbs.layout.page-header title="Übersicht für den Vorstand" lead="Kennzahlen zum Verein. Es werden nur Zahlen gezeigt, keine Personendaten." :breadcrumb="$crumbs" />

    <h2 style="font-size:var(--font-size-h3)">Mitgliedschaften</h2>
    <x-vdbs.layout.grid :columns="4">
        <x-vdbs.data.stat label="Aktive Mitglieder" :value="$stats['members']" tone="success" :href="route('membership.index')" />
        <x-vdbs.data.stat label="Anträge ohne Entscheidung" :value="$stats['requested']" :tone="$stats['requested'] > 0 ? 'warning' : 'neutral'" :href="route('membership.index')" />
        <x-vdbs.data.stat label="Geplante Austritte" :value="$stats['leaving']" hint="Mitglieder mit eingetragenem Austrittsdatum" />
        @can('access_requests.view')
            <x-vdbs.data.stat label="Neue Zugangsanträge" :value="$stats['accessRequests']" :tone="$stats['accessRequests'] > 0 ? 'warning' : 'neutral'" :href="route('access-requests.index')" />
        @endcan
    </x-vdbs.layout.grid>

    @can('board.meetings')
        <h2 style="margin-top:var(--space-8);font-size:var(--font-size-h3)">Vorstandsarbeit</h2>
        <x-vdbs.layout.grid :columns="4">
            <x-vdbs.data.stat label="Nächste Sitzung" :value="$stats['nextMeeting'] ?? 'keine geplant'" :href="route('board.meetings.index')" />
            <x-vdbs.data.stat label="Offene Aufgaben" :value="$stats['boardTasksOpen']" :href="route('board.tasks.index')" />
            <x-vdbs.data.stat label="Überfällige Aufgaben" :value="$stats['boardTasksOverdue']" :tone="$stats['boardTasksOverdue'] > 0 ? 'warning' : 'neutral'" :href="route('board.tasks.index', ['overdue' => 1])" />
        </x-vdbs.layout.grid>
    @endcan

    <h2 style="margin-top:var(--space-8);font-size:var(--font-size-h3)">Teamende</h2>
    <x-vdbs.layout.grid :columns="4">
        <x-vdbs.data.stat label="Teamende" :value="$stats['team']" />
        <x-vdbs.data.stat label="Einarbeitung offen" :value="$stats['onboarding']" :tone="$stats['onboarding'] > 0 ? 'warning' : 'neutral'" :href="route('team-onboarding.index')" />
    </x-vdbs.layout.grid>

    <h2 style="margin-top:var(--space-8);font-size:var(--font-size-h3)">Seminare und Veranstaltungen</h2>
    <x-vdbs.layout.grid :columns="4">
        <x-vdbs.data.stat label="Seminare in Anfrage oder Planung" :value="$stats['seminarsPlanning']" :href="route('seminars.index')" />
        <x-vdbs.data.stat label="Terminierte und bestätigte Seminare" :value="$stats['seminarsPlanned']" tone="success" :href="route('seminars.index')" />
        <x-vdbs.data.stat label="Veranstaltungen mit offener Anmeldung" :value="$stats['events']" :href="route('events.admin.index')" />
    </x-vdbs.layout.grid>
</x-layouts.app>
