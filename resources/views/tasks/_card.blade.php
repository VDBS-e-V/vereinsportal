@php
    $prioTone = ['high' => 'error', 'normal' => 'neutral', 'low' => 'neutral'];
@endphp
<li class="kb-card kb-card--{{ $task->priority }}" draggable="true" data-task-id="{{ $task->id }}" data-move-url="{{ route('tasks.move', $task) }}">
    <a class="kb-card__title" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
    <div class="kb-card__meta">
        @if ($task->priority === 'high')<x-vdbs.data.badge tone="error">Hoch</x-vdbs.data.badge>@endif
        @if ($task->due_on)<x-vdbs.data.badge :tone="$task->isOverdue() ? 'error' : 'neutral'">{{ $task->due_on->format('d.m.Y') }}</x-vdbs.data.badge>@endif
        @if ($task->meeting)<span class="vdbs-hint">Sitzung {{ $task->meeting->starts_at->format('d.m.Y') }}</span>@endif
    </div>
    @if ($task->assignees->isNotEmpty())<div class="kb-card__who">{{ $task->assigneeNames() }}</div>@endif
    <details class="kb-card__move">
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
</li>
