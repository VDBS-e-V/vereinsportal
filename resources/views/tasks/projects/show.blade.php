@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name]];
    $query = fn (array $extra = []) => array_filter(array_merge(request()->only(['q', 'assignee', 'priority', 'label', 'status', 'overdue']), $extra), fn ($value) => $value !== null && $value !== '');
    $byStatus = $tasks->groupBy('task_status_id');
    $assigneeOptions = ['me' => 'Ich', 'none' => 'Niemand'] + $assignable;
@endphp

<x-layouts.app :title="$project->name" width="full">
    <x-vdbs.layout.container>
        <x-vdbs.layout.page-header :title="$project->name" :lead="$project->description ?: 'Aufgaben des Projekts.'" :breadcrumb="$crumbs">
            <x-slot:actions>
                <x-vdbs.actions.button :variant="$view === 'board' ? 'primary' : 'secondary'" :href="route('tasks.projects.show', [$project] + $query(['view' => 'board']))" icon="square-kanban">Kanban</x-vdbs.actions.button>
                <x-vdbs.actions.button :variant="$view === 'list' ? 'primary' : 'secondary'" :href="route('tasks.projects.show', [$project] + $query(['view' => 'list']))" icon="clipboard-list">Liste</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('tasks.templates.index', $project)" icon="copy">Vorlagen</x-vdbs.actions.button>
                @if ($canManage)<x-vdbs.actions.button variant="secondary" :href="route('tasks.projects.edit', $project)" icon="settings">Einstellungen</x-vdbs.actions.button>@endif
            </x-slot:actions>
        </x-vdbs.layout.page-header>

        <form method="get" action="{{ route('tasks.projects.show', $project) }}" class="vdbs-form">
            <input type="hidden" name="view" value="{{ $view }}">
            <x-vdbs.forms.input name="q" label="Suchen" type="search" :optional="true" maxlength="100" :value="$filters['q'] ?? ''" />
            <x-vdbs.forms.select name="assignee" label="Zuständig" :optional="true" placeholder="Alle" :options="$assigneeOptions" :value="$filters['assignee'] ?? ''" />
            @if ($labels !== [])<x-vdbs.forms.select name="label" label="Label" :optional="true" placeholder="Alle" :options="$labels" :value="$filters['label'] ?? ''" />@endif
            <x-vdbs.forms.select name="priority" label="Priorität" :optional="true" placeholder="Alle" :options="$priorities" :value="$filters['priority'] ?? ''" />
            @if ($view === 'list')<x-vdbs.forms.select name="status" label="Anzeigen" :options="['open' => 'Offene', 'done' => 'Erledigte', 'all' => 'Alle']" :value="$filters['status'] ?? 'open'" />@endif
            <x-vdbs.forms.checkbox name="overdue" label="Nur Überfällige" value="1" :checked="$overdue" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
        </form>
    </x-vdbs.layout.container>

    @if ($view === 'board')
        <x-vdbs.data.kanban :label="'Kanban-Tafel '.$project->name">
            @foreach ($statuses as $status)
                <x-vdbs.data.kanban-column :title="$status->name" :status-id="$status->id" :count="($byStatus[$status->id] ?? collect())->count()" :done="$status->is_done">
                    @foreach ($byStatus[$status->id] ?? [] as $task)
                        @include('tasks._card', ['task' => $task, 'statuses' => $statuses])
                    @endforeach
                    <x-slot:footer>
                        <form method="post" action="{{ route('tasks.store', $project) }}">
                            @csrf
                            <input type="hidden" name="task_status_id" value="{{ $status->id }}">
                            <label class="vdbs-sr-only" for="add-{{ $status->id }}">Neue Aufgabe in {{ $status->name }}</label>
                            <input id="add-{{ $status->id }}" class="vdbs-input" type="text" name="title" maxlength="200" placeholder="Neue Aufgabe" required>
                            <x-vdbs.actions.button type="submit" size="s" variant="secondary" icon="plus">Hinzufügen</x-vdbs.actions.button>
                        </form>
                    </x-slot:footer>
                </x-vdbs.data.kanban-column>
            @endforeach
        </x-vdbs.data.kanban>
    @else
        <x-vdbs.layout.container>
            @if ($tasks->isEmpty())
                <x-vdbs.feedback.empty-state title="Keine Aufgaben" icon="clipboard-list"><p>Zu diesem Filter gibt es keine Aufgaben.</p></x-vdbs.feedback.empty-state>
            @else
                <form id="tasks-form" method="post" action="{{ route('tasks.bulk', $project) }}">
                    @csrf
                    <x-vdbs.data.table caption="Aufgaben" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'tasks-form'], ['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'who', 'label' => 'Zuständig'], ['key' => 'due', 'label' => 'Fällig'], ['key' => 'labels', 'label' => 'Labels'], ['key' => 'prio', 'label' => 'Priorität'], ['key' => 'state', 'label' => 'Spalte']]">
                        @foreach ($tasks as $task)
                            <tr>
                                <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $task->id }}" aria-label="{{ $task->title }} auswählen"></span></td>
                                <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>@if ($task->subtasks->isNotEmpty()) <span class="vdbs-hint">☑ {{ $task->subtaskProgress() }}</span>@endif</td>
                                <td>{{ $task->assigneeNames() ?: '–' }}</td>
                                <td>{{ $task->due_on?->format('d.m.Y') ?? '–' }}@if ($task->isOverdue()) <x-vdbs.data.badge tone="error">überfällig</x-vdbs.data.badge>@endif</td>
                                <td>@foreach ($task->labels as $label)@include('tasks._label', ['label' => $label])@endforeach</td>
                                <td>{{ $task->priorityLabel() }}</td>
                                <td><x-vdbs.data.badge :tone="$task->isDone() ? 'success' : 'neutral'">{{ $task->status->name }}</x-vdbs.data.badge></td>
                            </tr>
                        @endforeach
                    </x-vdbs.data.table>
                    <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="tasks-form">Nichts ausgewählt.</p>
                    @error('ids')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    @error('status_id')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    @error('assignees')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    @error('labels')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    <fieldset class="vdbs-fieldset">
                        <legend class="vdbs-fieldset__legend">Mit der Auswahl</legend>
                        <div class="vdbs-btn-group">
                            <x-vdbs.actions.button type="submit" name="action" value="done" icon="check">Als erledigt markieren</x-vdbs.actions.button>
                            <x-vdbs.actions.button type="submit" name="action" value="open" variant="secondary" icon="refresh-cw">Wieder öffnen</x-vdbs.actions.button>
                            @if ($canManage)<x-vdbs.actions.button type="submit" name="action" value="delete" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Aufgaben löschen?')">Löschen</x-vdbs.actions.button>@endif
                        </div>
                        <x-vdbs.forms.select name="status_id" label="In Spalte verschieben" :optional="true" placeholder="Spalte wählen" :options="$statuses->pluck('name', 'id')->all()" />
                        <x-vdbs.actions.button type="submit" name="action" value="status" variant="secondary">Verschieben</x-vdbs.actions.button>
                        <x-vdbs.forms.multi-select name="assignees" label="Zuweisen an" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$assignable" :value="[]" hint="Die Personen werden zu den bisherigen Zuständigen hinzugefügt." />
                        <x-vdbs.actions.button type="submit" name="action" value="assign" variant="secondary">Zuweisen</x-vdbs.actions.button>
                        @if ($labels !== [])
                            <x-vdbs.forms.multi-select name="labels" label="Label hinzufügen" :optional="true" placeholder="Kein Label ausgewählt" :options="$labels" :value="[]" />
                            <x-vdbs.actions.button type="submit" name="action" value="label" variant="secondary">Label hinzufügen</x-vdbs.actions.button>
                        @endif
                    </fieldset>
                </form>
            @endif
        </x-vdbs.layout.container>
    @endif

    <x-vdbs.layout.container>
        <x-vdbs.layout.section title="Neue Aufgabe">
            <x-vdbs.forms.form :action="route('tasks.store', $project)" method="post" :summary="false">
                @include('tasks._fields', ['task' => null, 'resolutions' => [], 'statuses' => $statuses, 'showStatus' => true, 'labels' => $labels])
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aufgabe anlegen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </x-vdbs.layout.section>
        @if ($templates !== [])
            <x-vdbs.layout.section title="Neue Aufgabe aus Vorlage">
                <x-vdbs.forms.form :action="route('tasks.templates.use', $project)" method="post" :summary="false">
                    <x-vdbs.forms.select name="template" label="Vorlage" :required="true" placeholder="Vorlage wählen" :options="$templates" :value="old('template')" />
                    <x-vdbs.forms.input name="title" label="Titel (leer = Titel der Vorlage)" :optional="true" maxlength="200" />
                    <x-vdbs.forms.multi-select name="assignees" label="Zuständig" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$assignable" :value="[]" />
                    <x-vdbs.forms.input name="due_on" label="Fällig am (leer = laut Vorlage)" type="date" :optional="true" />
                    <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aus Vorlage anlegen</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            </x-vdbs.layout.section>
        @endif
    </x-vdbs.layout.container>
</x-layouts.app>
