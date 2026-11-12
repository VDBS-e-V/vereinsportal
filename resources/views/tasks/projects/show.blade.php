@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name]];
    $query = fn (array $extra = []) => array_filter(array_merge(request()->only(['q', 'assignee', 'priority', 'status', 'overdue']), $extra), fn ($value) => $value !== null && $value !== '');
    $byStatus = $tasks->groupBy('task_status_id');
    $assigneeOptions = ['me' => 'Ich', 'none' => 'Niemand'] + $assignable;
@endphp

<x-layouts.app :title="$project->name" width="full">
    <x-vdbs.layout.container>
        <x-vdbs.layout.page-header :title="$project->name" :lead="$project->description ?: 'Aufgaben des Projekts.'" :breadcrumb="$crumbs">
            <x-slot:actions>
                <x-vdbs.actions.button :variant="$view === 'board' ? 'primary' : 'secondary'" :href="route('tasks.projects.show', [$project] + $query(['view' => 'board']))" icon="square-kanban">Kanban</x-vdbs.actions.button>
                <x-vdbs.actions.button :variant="$view === 'list' ? 'primary' : 'secondary'" :href="route('tasks.projects.show', [$project] + $query(['view' => 'list']))" icon="clipboard-list">Liste</x-vdbs.actions.button>
                @if ($canManage)<x-vdbs.actions.button variant="secondary" :href="route('tasks.projects.edit', $project)" icon="settings">Einstellungen</x-vdbs.actions.button>@endif
            </x-slot:actions>
        </x-vdbs.layout.page-header>

        <form method="get" action="{{ route('tasks.projects.show', $project) }}" class="vdbs-form">
            <input type="hidden" name="view" value="{{ $view }}">
            <x-vdbs.forms.input name="q" label="Suchen" type="search" :optional="true" maxlength="100" :value="$filters['q'] ?? ''" />
            <x-vdbs.forms.select name="assignee" label="Zuständig" :optional="true" placeholder="Alle" :options="$assigneeOptions" :value="$filters['assignee'] ?? ''" />
            <x-vdbs.forms.select name="priority" label="Priorität" :optional="true" placeholder="Alle" :options="$priorities" :value="$filters['priority'] ?? ''" />
            @if ($view === 'list')<x-vdbs.forms.select name="status" label="Anzeigen" :options="['open' => 'Offene', 'done' => 'Erledigte', 'all' => 'Alle']" :value="$filters['status'] ?? 'open'" />@endif
            <x-vdbs.forms.checkbox name="overdue" label="Nur Überfällige" value="1" :checked="$overdue" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
        </form>
    </x-vdbs.layout.container>

    @if ($view === 'board')
        <style>
            .kb{display:flex;gap:var(--space-4);align-items:flex-start;overflow-x:auto;padding:var(--space-4) var(--space-4) var(--space-6)}
            .kb-col{flex:0 0 18rem;background:var(--color-surface-sunken,#f1f5f9);border-radius:var(--radius-m,.5rem);padding:var(--space-3);min-height:8rem}
            .kb-col__head{display:flex;justify-content:space-between;align-items:center;margin:0 0 var(--space-3);font-size:var(--font-size-body,1rem);font-weight:600}
            .kb-list{list-style:none;margin:0;padding:0;display:grid;gap:var(--space-2);min-height:3rem}
            .kb-col.is-over{outline:2px dashed var(--color-focus,#2563eb)}
            .kb-card{background:var(--color-surface,#fff);border:1px solid var(--color-border,#cbd5e1);border-radius:var(--radius-s,.375rem);padding:var(--space-3);cursor:grab}
            .kb-card--high{border-left:4px solid var(--color-error,#b91c1c)}
            .kb-card.is-dragging{opacity:.5}
            .kb-card__title{font-weight:600;display:block;margin-bottom:var(--space-1)}
            .kb-card__meta{display:flex;gap:var(--space-2);flex-wrap:wrap;align-items:center}
            .kb-card__who{margin-top:var(--space-1);font-size:.875rem}
            .kb-card__move{margin-top:var(--space-2);font-size:.875rem}
            .kb-add{margin-top:var(--space-3)}
        </style>
        <div class="kb" data-kanban data-token="{{ csrf_token() }}">
            @foreach ($statuses as $status)
                <section class="kb-col" data-column data-status-id="{{ $status->id }}" aria-labelledby="col-{{ $status->id }}">
                    <h2 class="kb-col__head" id="col-{{ $status->id }}"><span>{{ $status->name }}@if ($status->is_done) <span class="vdbs-hint">(erledigt)</span>@endif</span><span data-count>{{ ($byStatus[$status->id] ?? collect())->count() }}</span></h2>
                    <ul class="kb-list" data-list>
                        @foreach ($byStatus[$status->id] ?? [] as $task)
                            @include('tasks._card', ['task' => $task, 'statuses' => $statuses])
                        @endforeach
                    </ul>
                    <form class="kb-add" method="post" action="{{ route('tasks.store', $project) }}">
                        @csrf
                        <input type="hidden" name="task_status_id" value="{{ $status->id }}">
                        <label class="vdbs-sr-only" for="add-{{ $status->id }}">Neue Aufgabe in {{ $status->name }}</label>
                        <input id="add-{{ $status->id }}" class="vdbs-input" type="text" name="title" maxlength="200" placeholder="Neue Aufgabe" required>
                        <x-vdbs.actions.button type="submit" size="s" variant="secondary" icon="plus">Hinzufügen</x-vdbs.actions.button>
                    </form>
                </section>
            @endforeach
        </div>
        <script src="{{ asset('vendor/kanban/kanban.js') }}" defer></script>
    @else
        <x-vdbs.layout.container>
            @if ($tasks->isEmpty())
                <x-vdbs.feedback.empty-state title="Keine Aufgaben" icon="clipboard-list"><p>Zu diesem Filter gibt es keine Aufgaben.</p></x-vdbs.feedback.empty-state>
            @else
                <form id="tasks-form" method="post" action="{{ route('tasks.bulk', $project) }}">
                    @csrf
                    <x-vdbs.data.table caption="Aufgaben" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'tasks-form'], ['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'who', 'label' => 'Zuständig'], ['key' => 'due', 'label' => 'Fällig'], ['key' => 'prio', 'label' => 'Priorität'], ['key' => 'state', 'label' => 'Spalte']]">
                        @foreach ($tasks as $task)
                            <tr>
                                <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $task->id }}" aria-label="{{ $task->title }} auswählen"></span></td>
                                <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                                <td>{{ $task->assigneeNames() ?: '–' }}</td>
                                <td>{{ $task->due_on?->format('d.m.Y') ?? '–' }}@if ($task->isOverdue()) <x-vdbs.data.badge tone="error">überfällig</x-vdbs.data.badge>@endif</td>
                                <td>{{ $task->priorityLabel() }}</td>
                                <td><x-vdbs.data.badge :tone="$task->isDone() ? 'success' : 'neutral'">{{ $task->status->name }}</x-vdbs.data.badge></td>
                            </tr>
                        @endforeach
                    </x-vdbs.data.table>
                    <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="tasks-form">Nichts ausgewählt.</p>
                    @error('ids')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    @error('status_id')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
                    @error('assignees')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
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
                    </fieldset>
                </form>
            @endif
        </x-vdbs.layout.container>
    @endif

    <x-vdbs.layout.container>
        <x-vdbs.layout.section title="Neue Aufgabe">
            <x-vdbs.forms.form :action="route('tasks.store', $project)" method="post" :summary="false">
                @include('tasks._fields', ['task' => null, 'resolutions' => [], 'statuses' => $statuses, 'showStatus' => true])
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aufgabe anlegen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        </x-vdbs.layout.section>
    </x-vdbs.layout.container>
</x-layouts.app>
