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
        @include('tasks._fields', ['task' => $task, 'showStatus' => true, 'labels' => $labels])
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.projects.show', $project)">Zur Tafel</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($task->labels->isNotEmpty())<p>@foreach ($task->labels as $label)@include('tasks._label', ['label' => $label]) @endforeach</p>@endif
    <p class="vdbs-hint">Angelegt am {{ $task->created_at->format('d.m.Y') }}@if ($task->creator) von {{ \App\Modules\Board\Models\BoardMeeting::attendeeName($task->creator) }}@endif.</p>

    <section aria-labelledby="subtasks-heading" style="margin-top:var(--space-6)">
        <h2 id="subtasks-heading" style="font-size:var(--font-size-h3)">Unteraufgaben @if ($task->subtasks->isNotEmpty())<span class="vdbs-hint">({{ $task->subtaskProgress() }} erledigt)</span>@endif</h2>
        @forelse ($task->subtasks as $subtask)
            <div style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:var(--space-1)">
                <form method="post" action="{{ route('tasks.subtasks.toggle', [$task, $subtask]) }}">@csrf
                    <x-vdbs.actions.button type="submit" size="s" :variant="$subtask->isDone() ? 'primary' : 'secondary'" :icon="$subtask->isDone() ? 'check' : null" :label="($subtask->isDone() ? 'Als offen markieren: ' : 'Als erledigt markieren: ').$subtask->title">{{ $subtask->isDone() ? 'Erledigt' : 'Offen' }}</x-vdbs.actions.button>
                </form>
                <span @if ($subtask->isDone()) style="text-decoration:line-through" @endif>{{ $subtask->title }}</span>
                <form method="post" action="{{ route('tasks.subtasks.destroy', [$task, $subtask]) }}">@csrf @method('delete')
                    <x-vdbs.actions.button type="submit" size="s" variant="danger-text" :label="'Unteraufgabe löschen: '.$subtask->title">Löschen</x-vdbs.actions.button>
                </form>
            </div>
        @empty
            <p>Noch keine Unteraufgaben.</p>
        @endforelse
        <x-vdbs.forms.form :action="route('tasks.subtasks.store', $task)" method="post" :summary="false">
            <x-vdbs.forms.textarea name="title" label="Neue Unteraufgabe" :required="true" maxlength="2000" :rows="2" hint="Eine Zeile pro Unteraufgabe: Mehrere Zeilen legen mehrere Unteraufgaben an." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Hinzufügen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    <section aria-labelledby="attachments-heading" style="margin-top:var(--space-6)">
        <h2 id="attachments-heading" style="font-size:var(--font-size-h3)">Anhänge</h2>
        @error('attachments')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
        @if ($task->attachments->isEmpty())
            <p>Noch keine Anhänge.</p>
        @else
            <form id="task-attachments-form" method="post" action="{{ route('tasks.attachments.destroy', $task) }}">@csrf
                <x-vdbs.data.table caption="Anhänge der Aufgabe" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'task-attachments-form'], ['key' => 'title', 'label' => 'Titel'], ['key' => 'date', 'label' => 'Abgelegt'], ['key' => 'by', 'label' => 'Von'], ['key' => 'size', 'label' => 'Größe']]">
                    @foreach ($task->attachments as $attachment)
                        <tr>
                            <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $attachment->id }}" form="task-attachments-form" aria-label="{{ $attachment->title }} auswählen"></span></td>
                            <td><a href="{{ route('tasks.attachments.download', [$task, $attachment]) }}">{{ $attachment->title }}</a><br><span class="vdbs-hint">{{ $attachment->original_name }}</span></td>
                            <td>{{ $attachment->created_at->format('d.m.Y') }}</td>
                            <td>{{ $attachment->uploadedBy?->person?->fullName() ?: '–' }}</td>
                            <td>{{ $attachment->sizeLabel() }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="task-attachments-form">Nichts ausgewählt.</p>
                <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Anhänge löschen?')">Auswahl löschen</x-vdbs.actions.button>
            </form>
        @endif
        <x-vdbs.forms.form :action="route('tasks.attachments.store', $task)" method="post" :files="true" :summary="false">
            <x-vdbs.forms.file name="files" label="Dateien hinzufügen" :multiple="true" :required="true" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.odt,.ods,.zip" hint="PDF, Word, Excel, CSV, OpenDocument, ZIP oder Text. Bilder sind nicht erlaubt. Bis zu 20 Dateien auf einmal." />
            <x-vdbs.forms.input name="title" label="Titel" :optional="true" maxlength="200" hint="Ohne Titel gilt der Dateiname." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="upload">Hochladen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

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

    @if ($canManage)
        <x-vdbs.forms.form :action="route('tasks.templates.from-task', $task)" method="post" :summary="false" style="margin-top:var(--space-6)">
            <x-vdbs.forms.input name="name" label="Als Vorlage speichern unter dem Namen" :required="true" maxlength="120" hint="Übernimmt Titel, Beschreibung, Priorität, Labels und Unteraufgaben, aber keine Zuständigen und keine Fälligkeit." />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="copy">Als Vorlage speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @if ($canManage || $task->created_by === $userId)
        <form method="post" action="{{ route('tasks.destroy', $task) }}" style="margin-top:var(--space-8)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Diese Aufgabe samt Kommentaren löschen?')">Aufgabe löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
