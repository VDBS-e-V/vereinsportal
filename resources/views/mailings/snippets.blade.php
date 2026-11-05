@php
    $editing = $edit !== null;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen', 'href' => route('mailings.templates.index')], ['label' => 'Textbausteine']];
@endphp

<x-layouts.app title="Textbausteine" width="wide">
    <x-vdbs.layout.page-header title="Textbausteine" lead="Kurze Abschnitte, die sich beim Schreiben eines Rundschreibens mit einem Klick in den Text einfügen lassen, zum Beispiel Hinweise zur Anmeldung oder zum Datenschutz. Das Einfügen kopiert den Text: Änderungen am Baustein wirken nicht auf bereits geschriebene Schreiben." :breadcrumb="$crumbs" />

    <section aria-labelledby="list-title">
        <h2 id="list-title" style="font-size:var(--font-size-h3)">Vorhandene Bausteine</h2>
        <form method="get" action="{{ route('mailings.templates.snippets') }}" style="display:flex;gap:var(--space-3);align-items:flex-end;flex-wrap:wrap;margin-bottom:var(--space-4)">
            <x-vdbs.forms.input name="q" label="Suchen" :value="$search" />
            <x-vdbs.forms.select name="kategorie" label="Kategorie" placeholder="Alle Kategorien" :options="$categories" :value="$category" />
            <x-vdbs.actions.button type="submit" variant="secondary" size="s">Filtern</x-vdbs.actions.button>
        </form>

        @if ($snippets->isEmpty())
            <p>Keine Textbausteine gefunden.</p>
        @else
            <form method="post" action="{{ route('mailings.templates.snippets.destroy-many') }}">
            @csrf
            <x-vdbs.data.table caption="Textbausteine" :columns="[['key' => 'sel', 'label' => 'Auswahl'], ['key' => 'name', 'label' => 'Name'], ['key' => 'category', 'label' => 'Kategorie'], ['key' => 'actions', 'label' => 'Aktionen']]">
                @foreach ($snippets as $snippet)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $snippet->id }}" aria-label="{{ $snippet->name }} auswählen"></td>
                        <td>{{ $snippet->name }}</td>
                        <td>{{ \App\Modules\Mailings\Support\MailCategories::snippetLabel($snippet->category) }}</td>
                        <td>
                            <x-vdbs.actions.button size="s" variant="text" :href="route('mailings.templates.snippets', ['bearbeiten' => $snippet->id])">Bearbeiten</x-vdbs.actions.button>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Bausteine löschen?')">Ausgewählte löschen</x-vdbs.actions.button>
            </form>
        @endif
    </section>

    <x-vdbs.forms.form :action="route('mailings.templates.snippets.store')" method="post" style="margin-top:var(--space-8)">
        <input type="hidden" name="snippet_id" value="{{ $edit?->id }}">
        <x-vdbs.forms.form-section :title="$editing ? 'Baustein bearbeiten' : 'Neuer Baustein'">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="$edit?->name" />
            <x-vdbs.forms.select name="category" label="Kategorie" :optional="true" placeholder="Ohne Kategorie" :options="$categories" :value="$edit?->category" />
            <x-vdbs.forms.input name="sort" label="Reihenfolge" :optional="true" type="number" :value="$edit?->sort ?? 0" hint="Kleinere Zahlen stehen weiter oben." />
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
