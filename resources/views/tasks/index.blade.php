@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben']];
@endphp

<x-layouts.app title="Aufgaben" width="wide">
    <x-vdbs.layout.page-header title="Aufgaben" lead="Projekte mit Kanban-Tafel und Liste. Sie sehen die Projekte, in denen Sie Mitglied sind." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.mine')" icon="check">Meine Aufgaben</x-vdbs.actions.button>
            @if ($isAdmin)
                <x-vdbs.actions.button :href="route('tasks.projects.create')" icon="plus">Neues Projekt</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($projects->isEmpty())
        <x-vdbs.feedback.empty-state title="{{ $showArchived ? 'Keine archivierten Projekte' : 'Noch keine Projekte' }}" icon="clipboard-list">
            <p>{{ $showArchived ? 'Es gibt keine archivierten Projekte.' : 'Sie sind noch in keinem Projekt Mitglied. Neue Projekte legt die Administration oder der Vorstand an.' }}</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Projekte" :columns="[['key' => 'name', 'label' => 'Projekt'], ['key' => 'open', 'label' => 'Offen'], ['key' => 'overdue', 'label' => 'Überfällig'], ['key' => 'mine', 'label' => 'Meine']]">
            @foreach ($projects as $project)
                <tr>
                    <td><a href="{{ route('tasks.projects.show', $project) }}">{{ $project->name }}</a>@if (filled($project->description))<br><span class="vdbs-hint">{{ \Illuminate\Support\Str::limit($project->description, 140) }}</span>@endif</td>
                    <td>{{ $project->open_count }}</td>
                    <td>@if ($project->overdue_count > 0)<x-vdbs.data.badge tone="error">{{ $project->overdue_count }}</x-vdbs.data.badge>@else 0 @endif</td>
                    <td>{{ $project->mine_count }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif

    <p style="margin-top:var(--space-4)">
        @if ($showArchived)<a href="{{ route('tasks.index') }}">Aktive Projekte anzeigen</a>@else<a href="{{ route('tasks.index', ['archived' => 1]) }}">Archivierte Projekte anzeigen</a>@endif
    </p>
</x-layouts.app>
