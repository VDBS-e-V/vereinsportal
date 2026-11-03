@php
    $editing = $edit !== null;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen', 'href' => route('mailings.templates.index')], ['label' => 'Textbausteine']];
@endphp

<x-layouts.app title="Textbausteine" width="wide">
    <x-vdbs.layout.page-header title="Textbausteine" lead="Kurze Abschnitte, die sich beim Schreiben eines Rundschreibens mit einem Klick in den Text einfügen lassen, zum Beispiel Hinweise zur Anmeldung oder zum Datenschutz. Das Einfügen kopiert den Text: Änderungen am Baustein wirken nicht auf bereits geschriebene Schreiben." :breadcrumb="$crumbs" />

    <section aria-labelledby="list-title">
        <h2 id="list-title" style="font-size:var(--font-size-h3)">Vorhandene Bausteine</h2>
        @if ($snippets->isEmpty())
            <p>Noch keine Textbausteine.</p>
        @else
            <x-vdbs.data.table caption="Textbausteine" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'actions', 'label' => 'Aktionen']]">
                @foreach ($snippets as $snippet)
                    <tr>
                        <td>{{ $snippet->name }}</td>
                        <td>
                            <x-vdbs.actions.button size="s" variant="text" :href="route('mailings.templates.snippets', ['bearbeiten' => $snippet->id])">Bearbeiten</x-vdbs.actions.button>
                            <form method="post" action="{{ route('mailings.templates.snippets.destroy', $snippet) }}" style="display:inline">@csrf @method('delete')<x-vdbs.actions.button size="s" variant="danger-text" type="submit">Löschen</x-vdbs.actions.button></form>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>

    <x-vdbs.forms.form :action="route('mailings.templates.snippets.store')" method="post" style="margin-top:var(--space-8)">
        <input type="hidden" name="snippet_id" value="{{ $edit?->id }}">
        <x-vdbs.forms.form-section :title="$editing ? 'Baustein bearbeiten' : 'Neuer Baustein'">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="$edit?->name" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="5000" :rows="8" :rich="true" :value="$body" />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            @if ($editing)
                <x-vdbs.actions.button variant="text" :href="route('mailings.templates.snippets')">Abbrechen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
