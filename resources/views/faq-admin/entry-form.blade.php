@php
    $editing = $entry->exists;
    $title = $editing ? 'Frage bearbeiten' : 'Neue Frage';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'FAQ', 'href' => route('faq-admin.index')], ['label' => $category->title, 'href' => route('faq-admin.entries.index', $category)], ['label' => $title]];
@endphp

<x-layouts.app :title="$title" width="medium">
    <x-vdbs.layout.page-header :title="$title" :lead="'Kategorie: '.$category->title" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('faq-admin.entries.update', [$category, $entry]) : route('faq-admin.entries.store', $category)" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Frage und Antwort">
            <x-vdbs.forms.input name="question" label="Frage" :required="true" maxlength="250" :value="$entry->question" />
            <x-vdbs.forms.textarea name="answer" label="Antwort" :required="true" :rows="10" maxlength="5000" :counter="true" :value="$entry->answer" hint="Leerzeile = neuer Absatz. Zeilen mit „- “ am Anfang = Liste. **fett**. [Text](/pfad) für Links im Portal, [Text](https://adresse) für externe Seiten." />
            <x-vdbs.forms.input name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="$entry->sort_order" hint="Kleine Zahlen stehen oben." />
            <x-vdbs.forms.checkbox name="is_active" label="Sichtbar" :checked="(bool) old('is_active', $entry->is_active)" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('faq-admin.entries.index', $category)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <form method="post" action="{{ route('faq-admin.entries.destroy', [$category, $entry]) }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Diese Frage löschen?')">Frage löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
