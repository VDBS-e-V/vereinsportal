@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Aufgaben']];
@endphp

<x-layouts.app title="Aufgaben" width="wide">
    <x-vdbs.layout.page-header title="Aufgaben des Vorstands" lead="Aufgaben aus Sitzungen und Beschlüssen mit Zuständigen und Fälligkeit. Mehrere Aufgaben können Sie zusammen erledigen oder löschen." :breadcrumb="$crumbs" />

    <form method="get" action="{{ route('board.tasks.index') }}" class="vdbs-form">
        <x-vdbs.forms.select name="status" label="Anzeigen" :options="['open' => 'Offene', 'done' => 'Erledigte', 'all' => 'Alle']" :value="$status" />
        <x-vdbs.forms.checkbox name="mine" label="Nur meine Aufgaben" value="1" :checked="$mine" />
        <x-vdbs.forms.checkbox name="overdue" label="Nur Überfällige" value="1" :checked="$overdue" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
    </form>

    @if ($tasks->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Aufgaben" icon="clipboard-list"><p>Zu diesem Filter gibt es keine Aufgaben.</p></x-vdbs.feedback.empty-state>
    @else
        <form id="tasks-form" method="post" action="{{ route('board.tasks.bulk') }}">
            @csrf
            <x-vdbs.data.table caption="Aufgaben" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'tasks-form'], ['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'who', 'label' => 'Zuständig'], ['key' => 'due', 'label' => 'Fällig'], ['key' => 'meeting', 'label' => 'Herkunft'], ['key' => 'state', 'label' => 'Stand']]">
                @foreach ($tasks as $task)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $task->id }}" aria-label="{{ $task->title }} auswählen"></span></td>
                        <td><a href="{{ route('board.tasks.edit', $task) }}">{{ $task->title }}</a>@if (filled($task->notes))<br><span class="vdbs-hint">{{ \Illuminate\Support\Str::limit($task->notes, 120) }}</span>@endif</td>
                        <td>{{ $task->assigneeNames() ?: '–' }}</td>
                        <td>{{ $task->due_on?->format('d.m.Y') ?? '–' }}@if ($task->isOverdue()) <x-vdbs.data.badge tone="error">überfällig</x-vdbs.data.badge>@endif</td>
                        <td>@if ($task->resolution){{ $task->resolution->number() }}<br>@endif @if ($task->meeting)<a href="{{ route('board.meetings.show', $task->meeting) }}">{{ $task->meeting->starts_at->format('d.m.Y') }}</a>@elseif (! $task->resolution) – @endif</td>
                        <td>@if ($task->isDone())<x-vdbs.data.badge tone="success">erledigt {{ $task->done_at->format('d.m.Y') }}</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">offen</x-vdbs.data.badge>@endif</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="tasks-form">Nichts ausgewählt.</p>
            <div class="vdbs-btn-group">
                <x-vdbs.actions.button type="submit" name="action" value="done" icon="check">Als erledigt markieren</x-vdbs.actions.button>
                <x-vdbs.actions.button type="submit" name="action" value="open" variant="secondary" icon="refresh-cw">Wieder öffnen</x-vdbs.actions.button>
                <x-vdbs.actions.button type="submit" name="action" value="delete" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Aufgaben löschen?')">Löschen</x-vdbs.actions.button>
            </div>
        </form>
    @endif

    <x-vdbs.layout.section title="Neue Aufgabe">
        <x-vdbs.forms.form :action="route('board.tasks.store')" method="post" :summary="false">
            @include('board.tasks._fields', ['task' => null, 'resolutions' => []])
            <x-vdbs.forms.select name="board_meeting_id" label="Aus Sitzung" :optional="true" placeholder="Keine Sitzung" :options="$meetings" :value="old('board_meeting_id')" />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aufgabe anlegen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </x-vdbs.layout.section>
</x-layouts.app>
