<x-vdbs.data.kanban-card :title="$task->title" :href="route('tasks.show', $task)" :move-url="route('tasks.move', $task)" :priority="$task->priority === 'high' ? 'high' : 'normal'">
    @if ($task->labels->isNotEmpty() || $task->due_on || $task->subtasks->isNotEmpty())
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;align-items:center">
            @foreach ($task->labels as $label)@include('tasks._label', ['label' => $label])@endforeach
            @if ($task->due_on)<x-vdbs.data.badge :tone="$task->isOverdue() ? 'error' : 'neutral'">{{ $task->due_on->format('d.m.Y') }}</x-vdbs.data.badge>@endif
            @if ($task->subtasks->isNotEmpty())<span class="vdbs-hint" title="Unteraufgaben erledigt">☑ {{ $task->subtaskProgress() }}</span>@endif
        </div>
    @endif
    @if ($task->meeting)<span class="vdbs-hint">Sitzung {{ $task->meeting->starts_at->format('d.m.Y') }}</span>@endif
    @if ($task->assignees->isNotEmpty())<span>{{ $task->assigneeNames() }}</span>@endif
    <x-slot:footer>
        <details>
            <summary>Verschieben</summary>
            <form method="post" action="{{ route('tasks.move', $task) }}">
                @csrf
                <label class="vdbs-sr-only" for="move-{{ $task->id }}">Spalte für {{ $task->title }}</label>
                <select id="move-{{ $task->id }}" name="status_id" class="vdbs-select">
                    @foreach ($statuses as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $task->task_status_id)>{{ $option->name }}</option>
                    @endforeach
                </select>
                <x-vdbs.actions.button type="submit" size="s" variant="secondary">Verschieben</x-vdbs.actions.button>
            </form>
        </details>
    </x-slot:footer>
</x-vdbs.data.kanban-card>
