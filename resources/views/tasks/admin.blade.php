@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Aufgaben verwalten']];
    $formId = 'bulk-task-projects';
    $areaLabels = collect(\App\Support\PortalNavigation::taskAreas())->mapWithKeys(fn ($area) => [$area->value => $area->label()]);
@endphp

<x-layouts.app title="Aufgaben verwalten" width="wide">
    <x-vdbs.layout.page-header title="Aufgaben verwalten" lead="Alle Projekte des Aufgabensystems, nach Bereichen: anlegen, archivieren, löschen und den Bereich festlegen, in dessen Menü „Aufgaben“ ein Projekt erscheint." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('tasks.projects.create')" icon="plus">Neues Projekt</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($projects->isEmpty())
        <x-vdbs.feedback.empty-state :title="$showArchived ? 'Keine archivierten Projekte' : 'Noch keine Projekte'" icon="clipboard-list">
            <p>{{ $showArchived ? 'Es gibt keine archivierten Projekte.' : 'Legen Sie das erste Projekt an.' }}</p>
        </x-vdbs.feedback.empty-state>
    @else
        <form method="post" action="{{ route('tasks.projects.bulk') }}" id="{{ $formId }}">
            @csrf
            <x-vdbs.data.table caption="Alle Projekte" :columns="[['key' => 'sel', 'label' => 'Auswahl', 'selectAll' => $formId], ['key' => 'name', 'label' => 'Projekt'], ['key' => 'area', 'label' => 'Bereich'], ['key' => 'open', 'label' => 'Offen'], ['key' => 'overdue', 'label' => 'Überfällig'], ['key' => 'actions', 'label' => 'Aktionen']]">
                @foreach ($projects as $project)
                    <tr>
                        <x-vdbs.data.table-check name="ids[]" :value="$project->id" :label="$project->name.' auswählen'" :form="$formId" />
                        <td><a href="{{ route('tasks.projects.show', $project) }}"><strong>{{ $project->name }}</strong></a>@if (filled($project->description))<br><span class="vdbs-hint">{{ \Illuminate\Support\Str::limit($project->description, 120) }}</span>@endif</td>
                        <td>{{ $areaLabels[$project->area] ?? $project->area }}</td>
                        <td>{{ $project->open_count }}</td>
                        <td>@if ($project->overdue_count > 0)<x-vdbs.data.badge tone="error">{{ $project->overdue_count }}</x-vdbs.data.badge>@else 0 @endif</td>
                        <td><a href="{{ route('tasks.projects.edit', $project) }}">Einstellungen</a></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="{{ $formId }}">Nichts ausgewählt.</p>

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center;margin-top:var(--space-4)">
                <label for="aktion">Mit den ausgewählten Projekten:</label>
                <select name="aktion" id="aktion" class="vdbs-control vdbs-control--size-s" style="width:auto">
                    @if ($showArchived)
                        <option value="reopen">wieder öffnen</option>
                    @else
                        <option value="archive">archivieren</option>
                    @endif
                    <option value="delete">löschen (mit allen Aufgaben)</option>
                </select>
                <x-vdbs.actions.button type="submit" variant="secondary" size="s" onclick="return confirm('Aktion für die ausgewählten Projekte ausführen? Beim Löschen verschwinden auch alle Aufgaben, Anhänge und Kommentare.')">Ausführen</x-vdbs.actions.button>
            </div>
        </form>
    @endif

    <p style="margin-top:var(--space-4)">
        @if ($showArchived)<a href="{{ route('tasks.index') }}">Aktive Projekte anzeigen</a>@else<a href="{{ route('tasks.index', ['archived' => 1]) }}">Archivierte Projekte anzeigen</a>@endif
    </p>
</x-layouts.app>
