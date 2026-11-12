@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Sitzungen', 'href' => route('board.meetings.index')], ['label' => $meeting->title]];
    $names = fn ($users) => $users->map(fn ($user) => \App\Modules\Board\Models\BoardMeeting::attendeeName($user))->sort()->implode(', ');
@endphp

<x-layouts.app :title="$meeting->title" width="wide">
    <x-vdbs.layout.page-header :title="$meeting->title" :lead="$meeting->period().' · '.$meeting->statusLabel()" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('board.meetings.edit', $meeting)" icon="pencil">Bearbeiten</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" href="#" icon="printer" onclick="window.print();return false;">Drucken</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.detail-list>
        <x-vdbs.data.detail-item label="Ort">{{ $meeting->location ?: '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Videokonferenz">@if ($meeting->online_link)<a href="{{ $meeting->online_link }}" target="_blank" rel="noopener noreferrer">{{ $meeting->online_link }}</a>@else – @endif</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anwesend">{{ $present->isNotEmpty() ? $names($present) : '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Entschuldigt">{{ $excused->isNotEmpty() ? $names($excused) : '–' }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Tagesordnung</h2>
        @if (\App\Support\Html\RichText::isEmpty($meeting->agenda))
            <p>Noch keine Tagesordnung.</p>
        @else
            <div class="vdbs-prose">{!! \App\Support\Html\RichText::clean($meeting->agenda) !!}</div>
        @endif
    </section>

    <section style="margin-top:var(--space-6)">
        <h2 style="font-size:var(--font-size-h3)">Protokoll @if ($meeting->minutes_approved_on)<x-vdbs.data.badge tone="success">genehmigt am {{ $meeting->minutes_approved_on->format('d.m.Y') }}</x-vdbs.data.badge>@elseif ($meeting->hasMinutes())<x-vdbs.data.badge tone="warning">Entwurf</x-vdbs.data.badge>@endif</h2>
        @if ($meeting->hasMinutes())
            <div class="vdbs-prose">{!! \App\Support\Html\RichText::clean($meeting->minutes) !!}</div>
            @unless ($meeting->minutes_approved_on)
                <form method="post" action="{{ route('board.meetings.approve', $meeting) }}">
                    @csrf
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="check">Als genehmigt vermerken</x-vdbs.actions.button>
                </form>
            @endunless
        @else
            <p>Noch kein Protokoll. <a href="{{ route('board.meetings.edit', $meeting) }}">Protokoll schreiben</a></p>
        @endif
    </section>

    <section aria-labelledby="resolutions-heading" style="margin-top:var(--space-6)">
        <h2 id="resolutions-heading" style="font-size:var(--font-size-h3)">Beschlüsse</h2>
        @if ($meeting->resolutions->isEmpty())
            <p>Noch keine Beschlüsse erfasst.</p>
        @else
            <x-vdbs.data.table caption="Beschlüsse der Sitzung" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'board-resolutions-form'], ['key' => 'number', 'label' => 'Nummer'], ['key' => 'title', 'label' => 'Beschluss'], ['key' => 'outcome', 'label' => 'Ergebnis']]">
                @foreach ($meeting->resolutions as $resolution)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $resolution->id }}" form="board-resolutions-form" aria-label="{{ $resolution->number() }} auswählen"></span></td>
                        <td>{{ $resolution->number() }}</td>
                        <td><a href="{{ route('board.resolutions.edit', $resolution) }}">{{ $resolution->title }}</a>@if (filled($resolution->text))<br><span style="white-space:pre-line">{{ $resolution->text }}</span>@endif @if ($resolution->votesLabel() !== '')<br><span class="vdbs-hint">{{ $resolution->votesLabel() }}</span>@endif</td>
                        <td>{{ $resolution->outcomeLabel() }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <form id="board-resolutions-form" method="post" action="{{ route('board.resolutions.destroy-many') }}" style="margin-top:var(--space-3)">@csrf
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="board-resolutions-form">Nichts ausgewählt.</p>
                <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Beschlüsse löschen? Verknüpfte Aufgaben bleiben erhalten.')">Auswahl löschen</x-vdbs.actions.button>
            </form>
        @endif
        <x-vdbs.forms.form :action="route('board.resolutions.store', $meeting)" method="post" :summary="false">
            @include('board.resolutions._fields', ['resolution' => null])
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Beschluss erfassen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    <section aria-labelledby="tasks-heading" style="margin-top:var(--space-6)">
        <h2 id="tasks-heading" style="font-size:var(--font-size-h3)">Aufgaben</h2>
        <p>Aufgaben dieser Sitzung stehen im Projekt „Vorstand“ des Aufgabensystems, dort auch als Kanban-Tafel: <a href="{{ route('tasks.projects.show', $taskProject) }}">Projekt öffnen</a>.</p>
        @if ($meeting->tasks->isEmpty())
            <p>Noch keine Aufgaben aus dieser Sitzung.</p>
        @else
            <x-vdbs.data.table caption="Aufgaben der Sitzung" :columns="[['key' => 'title', 'label' => 'Aufgabe'], ['key' => 'who', 'label' => 'Zuständig'], ['key' => 'due', 'label' => 'Fällig'], ['key' => 'state', 'label' => 'Stand']]">
                @foreach ($meeting->tasks as $task)
                    <tr>
                        <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                        <td>{{ $task->assigneeNames() ?: '–' }}</td>
                        <td>{{ $task->due_on?->format('d.m.Y') ?? '–' }}@if ($task->isOverdue()) <x-vdbs.data.badge tone="error">überfällig</x-vdbs.data.badge>@endif</td>
                        <td>{{ $task->status?->name }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
        <x-vdbs.forms.form :action="route('tasks.store', $taskProject)" method="post" :summary="false">
            <input type="hidden" name="board_meeting_id" value="{{ $meeting->id }}">
            <input type="hidden" name="from_meeting" value="1">
            <x-vdbs.forms.input name="title" label="Aufgabe" :required="true" maxlength="200" />
            <x-vdbs.forms.multi-select name="assignees" label="Zuständig" :optional="true" :searchable="true" placeholder="Niemand ausgewählt" search-label="Personen filtern" :options="$taskAssignable" :value="old('assignees', [])" hint="Mehrere Personen möglich." />
            <x-vdbs.forms.input name="due_on" label="Fällig am" type="date" :optional="true" />
            @if ($meeting->resolutions->isNotEmpty())
                <x-vdbs.forms.select name="board_resolution_id" label="Zu Beschluss" :optional="true" placeholder="Kein Beschluss" :options="$meeting->resolutions->mapWithKeys(fn ($resolution) => [$resolution->id => $resolution->number().' '.$resolution->title])->all()" :value="old('board_resolution_id')" />
            @endif
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Aufgabe anlegen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    <section aria-labelledby="guests-heading" style="margin-top:var(--space-6)">
        <h2 id="guests-heading" style="font-size:var(--font-size-h3)">Externe Gäste</h2>
        <p>Gäste ohne Portalkonto, zum Beispiel Beratende oder Partner. Sie erhalten nur die Einladung per E-Mail, keinen Zugang zum Portal. Name, E-Mail-Adresse und Organisation werden {{ max(1, (int) config('portal.board.guest_retention_days', 30)) }} Tage nach der Sitzung automatisch gelöscht; die Einladung enthält einen Datenschutzhinweis.</p>
        @if ($meeting->guests->isEmpty())
            <p>Noch keine Gäste eingetragen.</p>
        @else
            <x-vdbs.data.table caption="Gäste der Sitzung" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'board-guests-form'], ['key' => 'name', 'label' => 'Name'], ['key' => 'email', 'label' => 'E-Mail'], ['key' => 'invited', 'label' => 'Eingeladen']]">
                @foreach ($meeting->guests as $guest)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $guest->id }}" form="board-guests-form" aria-label="{{ $guest->name }} auswählen"></span></td>
                        <td>{{ $guest->label() }}</td>
                        <td>{{ $guest->email }}</td>
                        <td>{{ $guest->invited_at ? $guest->invited_at->format('d.m.Y, H:i').' Uhr' : '–' }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <form id="board-guests-form" method="post" action="{{ route('board.meetings.guests.destroy', $meeting) }}" style="margin-top:var(--space-3)">@csrf
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="board-guests-form">Nichts ausgewählt.</p>
                <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Gäste entfernen?')">Auswahl entfernen</x-vdbs.actions.button>
                @if ($invitations && $meeting->status !== 'cancelled')
                    <x-vdbs.forms.textarea name="note" label="Nachricht an die Gäste" :optional="true" maxlength="2000" :rows="3" hint="Erscheint in der E-Mail, zum Beispiel mit einem Hinweis zum Anlass der Einladung." />
                    <x-vdbs.forms.checkbox name="with_agenda" label="Tagesordnung in die E-Mail aufnehmen" value="1" hint="Nur ankreuzen, wenn die Tagesordnung für Externe bestimmt ist." />
                    <x-vdbs.actions.button type="submit" icon="mail" formaction="{{ route('board.meetings.guests.invite', $meeting) }}" onclick="return confirm('Die Einladung jetzt per E-Mail senden? Ohne Auswahl bekommen alle Gäste sie.')">Auswahl einladen (ohne Auswahl: alle)</x-vdbs.actions.button>
                @endif
            </form>
            @unless ($invitations)
                <p class="vdbs-hint">Einladungen per E-Mail sind ausgeschaltet. Sie schalten sie auf der Seite „Sitzungen“ ein.</p>
            @endunless
        @endif

        <x-vdbs.forms.form :action="route('board.meetings.guests.store', $meeting)" method="post" :summary="false">
            <x-vdbs.forms.input name="name" label="Name des Gastes" :required="true" maxlength="150" />
            <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" maxlength="190" />
            <x-vdbs.forms.input name="organization" label="Organisation" :optional="true" maxlength="150" hint="Zum Beispiel Verein, Schule oder Behörde." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Gast eintragen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    <section aria-labelledby="documents-heading" style="margin-top:var(--space-6)">
        <h2 id="documents-heading" style="font-size:var(--font-size-h3)">Anhänge</h2>
        @error('documents')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
        @if ($meeting->documents->isEmpty())
            <p>Noch keine Anhänge.</p>
        @else
            <x-vdbs.data.table caption="Anhänge der Sitzung" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'board-documents-form'], ['key' => 'title', 'label' => 'Titel'], ['key' => 'date', 'label' => 'Abgelegt'], ['key' => 'by', 'label' => 'Von'], ['key' => 'size', 'label' => 'Größe']]">
                @foreach ($meeting->documents as $document)
                    <tr>
                        <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $document->id }}" form="board-documents-form" aria-label="{{ $document->title }} auswählen"></span></td>
                        <td><a href="{{ route('board.meetings.documents.download', [$meeting, $document]) }}">{{ $document->title }}</a><br><span class="vdbs-hint">{{ $document->original_name }}</span></td>
                        <td>{{ $document->created_at->format('d.m.Y') }}</td>
                        <td>{{ $document->uploadedBy?->person?->fullName() ?: '–' }}</td>
                        <td>{{ $document->sizeLabel() }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <form id="board-documents-form" method="post" action="{{ route('board.meetings.documents.destroy', $meeting) }}" style="margin-top:var(--space-3)">@csrf
                <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="board-documents-form">Nichts ausgewählt.</p>
                <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Die ausgewählten Anhänge löschen?')">Auswahl löschen</x-vdbs.actions.button>
            </form>
        @endif

        <x-vdbs.forms.form :action="route('board.meetings.documents.store', $meeting)" method="post" :files="true" :summary="false">
            <x-vdbs.forms.file name="files" label="Dateien hinzufügen" :multiple="true" :required="true" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.odt,.ods,.zip" hint="PDF, Word, Excel, CSV, OpenDocument, ZIP oder Text. Bilder sind nicht erlaubt. Bis zu 20 Dateien auf einmal." />
            <x-vdbs.forms.input name="title" label="Titel" :optional="true" maxlength="200" hint="Ohne Titel gilt der Dateiname." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="upload">Hochladen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </section>

    @if ($invitations)
        <section aria-labelledby="invite-heading" style="margin-top:var(--space-6)">
            <h2 id="invite-heading" style="font-size:var(--font-size-h3)">Einladung</h2>
            @if ($meeting->status === 'cancelled')
                <p>Zu einer abgesagten Sitzung wird nicht eingeladen.</p>
            @else
                @if ($meeting->invited_at)
                    <p>Zuletzt eingeladen am {{ $meeting->invited_at->format('d.m.Y, H:i') }} Uhr.@if ($meeting->changedSinceInvitation()) <x-vdbs.data.badge tone="warning">Seitdem geändert</x-vdbs.data.badge>@endif</p>
                @else
                    <p>Noch nicht eingeladen.</p>
                @endif
                <x-vdbs.forms.form :action="route('board.meetings.invite', $meeting)" method="post" :summary="false">
                    <x-vdbs.forms.textarea name="note" label="Nachricht an den Vorstand" :optional="true" maxlength="2000" :rows="3" hint="Erscheint in der E-Mail über Termin und Tagesordnung, zum Beispiel bei einer Terminänderung." />
                    <x-slot:actions><x-vdbs.actions.button type="submit" icon="mail" onclick="return confirm('Die Einladung jetzt per E-Mail an den Vorstand senden?')">{{ $meeting->invited_at ? 'Erneut einladen' : 'Einladung senden' }}</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            @endif
        </section>
    @endif

    <form method="post" action="{{ route('board.meetings.destroy', $meeting) }}" style="margin-top:var(--space-8)">
        @csrf @method('delete')
        <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Diese Sitzung samt Protokoll, Beschlüssen und Anhängen löschen? Aufgaben bleiben erhalten.')">Sitzung löschen</x-vdbs.actions.button>
    </form>
</x-layouts.app>
