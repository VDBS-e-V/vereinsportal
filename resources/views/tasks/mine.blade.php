@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => 'Meine Aufgaben']];
@endphp

<x-layouts.app title="Meine Aufgaben" width="wide">
    <x-vdbs.layout.page-header title="Meine Aufgaben" lead="Alle Aufgaben, die Ihnen zugewiesen sind, aus allen Projekten, die Sie sehen dürfen." :breadcrumb="$crumbs" />

    <form method="get" action="{{ route('tasks.mine') }}" class="vdbs-form">
        <x-vdbs.forms.select name="status" label="Anzeigen" :options="['open' => 'Offene', 'done' => 'Erledigte', 'all' => 'Alle']" :value="$status" />
        <x-vdbs.forms.checkbox name="overdue" label="Nur Überfällige" value="1" :checked="$overdue" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
    </form>

    @if ($tasks->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Aufgaben" icon="clipboard-list"><p>Zu diesem Filter sind Ihnen keine Aufgaben zugewiesen.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Meine Aufgaben" :columns="[['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'project', 'label' => 'Projekt'], ['key' => 'due', 'label' => 'Fällig'], ['key' => 'prio', 'label' => 'Priorität'], ['key' => 'state', 'label' => 'Stand']]">
            @foreach ($tasks as $task)
                <tr>
                    <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                    <td><a href="{{ route('tasks.projects.show', $task->project) }}">{{ $task->project->name }}</a></td>
                    <td>{{ $task->due_on?->format('d.m.Y') ?? '–' }}@if ($task->isOverdue()) <x-vdbs.data.badge tone="error">überfällig</x-vdbs.data.badge>@endif</td>
                    <td>{{ $task->priorityLabel() }}</td>
                    <td><x-vdbs.data.badge :tone="$task->isDone() ? 'success' : 'neutral'">{{ $task->status->name }}</x-vdbs.data.badge></td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
