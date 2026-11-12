@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name, 'href' => route('tasks.projects.show', $project)], ['label' => $task->title]];
@endphp

<x-layouts.app :title="$task->title" width="wide">
    <x-vdbs.layout.page-header :title="$task->title" :lead="'Projekt „'.$project->name.'“ · Spalte „'.$task->status->name.'“'.($task->isDone() ? ' · erledigt am '.$task->done_at->format('d.m.Y') : '')" :breadcrumb="$crumbs">
        <x-slot:actions>
            @if ($task->meeting)<x-vdbs.actions.button variant="secondary" :href="route('board.meetings.show', $task->meeting)" icon="calendar">Zur Sitzung vom {{ $task->meeting->starts_at->format('d.m.Y') }}</x-vdbs.actions.button>@endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($task->resolution)<p>Zum Beschluss {{ $task->resolution->number() }}: {{ $task->resolution->title }}</p>@endif
    @if ($task->isOverdue())<x-vdbs.feedback.alert variant="warning">Die Aufgabe ist seit dem {{ $task->due_on->format('d.m.Y') }} überfällig.</x-vdbs.feedback.alert>@endif

    <x-vdbs.forms.form :action="route('tasks.update', $task)" method="put">
        @include('tasks._fields', ['task' => $task, 'showStatus' => true])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.projects.show', $project)">Zur Tafel</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <p class="vdbs-hint">Angelegt am {{ $task->created_at->format('d.m.Y') }}@if ($task->creator) von {{ \App\Modules\Board\Models\BoardMeeting::attendeeName($task->creator) }}@endif.</p>

    <section aria-labelledby="comments-heading" style="margin-top:var(--space-6)">
        <h2 id="comments-heading" style="font-size:var(--font-size-h3)">Kommentare</h2>
        @forelse ($task->comments as $comment)
            <article style="margin-bottom:var(--space-3)">
                <p class="vdbs-hint">{{ $comment->author ? \App\Modules\Board\Models\BoardMeeting::attendeeName($comment->author) : 'Gelöschtes Konto' }}, {{ $comment->created_at->format('d.m.Y, H:i') }} Uhr</p>
                <p style="white-space:pre-line;margin:0">{{ $comment->body }}</p>
                @if ($comment->user_id === $userId || $canManage)
                    <form method="post" action="{{ route('tasks.comments.destroy', [$task, $comment]) }}">@csrf @method('delete')
                        <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Den Kommentar löschen?')">Löschen</x-vdbs.actions.button>
                    </form>
                @endif
            </article>
        @empty
            <p>Noch keine Kommentare.</p>
        @endforelse
        <x-vdbs.forms.form :action="route('tasks.comments.store', $task)" method="post" :summary="false">
            <x-vdbs.forms.textarea name="body" label="Neuer Kommentar" :required="true" maxlength="2000" :rows="3" />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Kommentieren</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    @if ($canManage || $task->created_by === $userId)
        <form method="post" action="{{ route('tasks.destroy', $task) }}" style="margin-top:var(--space-8)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Diese Aufgabe samt Kommentaren löschen?')">Aufgabe löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
