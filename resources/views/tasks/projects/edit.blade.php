@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Aufgaben', 'href' => route('tasks.index')], ['label' => $project->name, 'href' => route('tasks.projects.show', $project)], ['label' => 'Einstellungen']];
    $roleEditable = $isAdmin && $project->slug !== \App\Modules\Tasks\Models\TaskProject::BOARD;
@endphp

<x-layouts.app title="Projekt bearbeiten" width="wide">
    <x-vdbs.layout.page-header :title="$project->name" lead="Einstellungen, Mitglieder und Spalten des Projekts." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('tasks.projects.update', $project)" method="put">
        @include('tasks.projects._fields', ['project' => $project, 'roleEditable' => $roleEditable])
        @if ($project->role_key && ! $roleEditable)
            <p class="vdbs-hint">Alle Konten mit der Rolle „{{ \App\Modules\AccessGovernance\Enums\RoleKey::tryFrom($project->role_key)?->label() }}“ sind automatisch Mitglieder und verwalten das Projekt.</p>
        @endif

        <x-vdbs.layout.section title="Spalten">
            <p>Die Spalten sind die Status der Aufgaben, von links nach rechts nach der Reihenfolge. Spalten, die als „erledigt“ gelten, schließen die Aufgaben darin ab. Löschen Sie eine Spalte, ziehen ihre Aufgaben in die erste verbleibende Spalte um.</p>
            @error('statuses')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
            <x-vdbs.data.table caption="Spalten" :columns="[['key' => 'pos', 'label' => 'Reihenfolge'], ['key' => 'name', 'label' => 'Name'], ['key' => 'done', 'label' => 'Gilt als erledigt'], ['key' => 'count', 'label' => 'Aufgaben'], ['key' => 'del', 'label' => 'Löschen']]">
                @foreach ($statuses as $status)
                    <tr>
                        <td><input type="number" min="0" max="99" class="vdbs-input" name="statuses[{{ $status->id }}][position]" value="{{ old('statuses.'.$status->id.'.position', $status->position) }}" aria-label="Reihenfolge von {{ $status->name }}" style="width:5rem"></td>
                        <td><input type="text" maxlength="60" class="vdbs-input" name="statuses[{{ $status->id }}][name]" value="{{ old('statuses.'.$status->id.'.name', $status->name) }}" aria-label="Name der Spalte" required></td>
                        <td><input type="checkbox" name="statuses[{{ $status->id }}][done]" value="1" @checked(old('statuses', null) === null ? $status->is_done : old('statuses.'.$status->id.'.done')) aria-label="{{ $status->name }} gilt als erledigt"></td>
                        <td>{{ $status->tasks_count }}</td>
                        <td><input type="checkbox" name="statuses[{{ $status->id }}][delete]" value="1" aria-label="{{ $status->name }} löschen"></td>
                    </tr>
                @endforeach
                @foreach ([0, 1] as $i)
                    <tr>
                        <td>neu</td>
                        <td><input type="text" maxlength="60" class="vdbs-input" name="new_statuses[{{ $i }}][name]" value="{{ old('new_statuses.'.$i.'.name') }}" aria-label="Name einer neuen Spalte" placeholder="Neue Spalte"></td>
                        <td><input type="checkbox" name="new_statuses[{{ $i }}][done]" value="1" aria-label="Neue Spalte gilt als erledigt"></td>
                        <td>–</td><td>–</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </x-vdbs.layout.section>

        <x-vdbs.layout.section title="Labels">
            <p>Labels ordnen Aufgaben ein, zum Beispiel „Fehler“ oder „Wunsch“. Die Farben sind die sechs Farben des Portals. Löschen Sie ein Label, verschwindet es von allen Aufgaben.</p>
            @error('labels')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
            <x-vdbs.data.table caption="Labels" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'color', 'label' => 'Farbe'], ['key' => 'count', 'label' => 'Aufgaben'], ['key' => 'del', 'label' => 'Löschen']]">
                @foreach ($labelRows as $label)
                    <tr>
                        <td><input type="text" maxlength="40" class="vdbs-input" name="labels[{{ $label->id }}][name]" value="{{ old('labels.'.$label->id.'.name', $label->name) }}" aria-label="Name des Labels" required></td>
                        <td><select class="vdbs-select" name="labels[{{ $label->id }}][color]" aria-label="Farbe von {{ $label->name }}">@foreach ($colors as $value => $text)<option value="{{ $value }}" @selected(old('labels.'.$label->id.'.color', $label->color) === (string) $value)>{{ $text }}</option>@endforeach</select> @include('tasks._label', ['label' => $label])</td>
                        <td>{{ $label->tasks_count }}</td>
                        <td><input type="checkbox" name="labels[{{ $label->id }}][delete]" value="1" aria-label="{{ $label->name }} löschen"></td>
                    </tr>
                @endforeach
                @foreach ([0, 1] as $i)
                    <tr>
                        <td><input type="text" maxlength="40" class="vdbs-input" name="new_labels[{{ $i }}][name]" value="{{ old('new_labels.'.$i.'.name') }}" aria-label="Name eines neuen Labels" placeholder="Neues Label"></td>
                        <td><select class="vdbs-select" name="new_labels[{{ $i }}][color]" aria-label="Farbe des neuen Labels">@foreach ($colors as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach</select></td>
                        <td>–</td><td>–</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </x-vdbs.layout.section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="secondary" :href="route('tasks.projects.show', $project)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($project->slug !== \App\Modules\Tasks\Models\TaskProject::BOARD)
        <div style="margin-top:var(--space-8);display:flex;gap:var(--space-3);flex-wrap:wrap">
            <form method="post" action="{{ route('tasks.projects.archive', $project) }}">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="folder">{{ $project->is_archived ? 'Wieder aktivieren' : 'Archivieren' }}</x-vdbs.actions.button>
            </form>
            @if ($isAdmin)
                <form method="post" action="{{ route('tasks.projects.destroy', $project) }}">@csrf @method('delete')
                    <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Das Projekt samt allen Aufgaben und Kommentaren endgültig löschen?')">Projekt löschen</x-vdbs.actions.button>
                </form>
            @endif
        </div>
    @endif
</x-layouts.app>
