@php
    $editing = $category->exists;
    $title = $editing ? 'Kategorie bearbeiten' : 'Neue Kategorie';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'FAQ', 'href' => route('faq-admin.index')], ['label' => $title]];
    $rows = array_values((array) old('entries', $entries));
    $next = count($rows);
    $rows[$next] = [];
    $nextIndex = $next + 1;
@endphp

<x-layouts.app :title="$title" width="wide">
    <x-vdbs.layout.page-header :title="$title" lead="Eine Kategorie fasst zusammen, was zusammengehört (z. B. „Zugang beantragen“). Kontaktthemen können auf eine Kategorie verweisen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('faq-admin.update', $category) : route('faq-admin.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Kategorie">
            <x-vdbs.forms.input name="title" label="Bezeichnung" :required="true" maxlength="150" :value="$category->title" />
            <x-vdbs.forms.input name="description" label="Kurzbeschreibung" :optional="true" maxlength="250" :value="$category->description" hint="Steht unter der Überschrift auf der FAQ-Seite." />
            <x-vdbs.forms.input name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="$category->sort_order" hint="Kleine Zahlen stehen oben." />
            <x-vdbs.forms.checkbox name="is_active" label="Sichtbar" :checked="(bool) old('is_active', $category->is_active)" hint="Ausgeblendete Kategorien erscheinen nirgends, auch nicht über Kontaktthemen." />
            <x-vdbs.forms.checkbox name="in_faq_page" label="Auf der großen FAQ-Seite zeigen" :checked="(bool) old('in_faq_page', $category->in_faq_page)" hint="Ohne Häkchen erscheinen die Fragen nur dort, wo ein Kontaktthema auf diese Kategorie verweist." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Fragen und Antworten" description="Leere Zeilen werden ignoriert, die Reihenfolge ist die der Zeilen. In der Antwort gilt: Leerzeile = neuer Absatz, Zeilen mit „- “ = Liste, **fett**, [Text](/pfad) für Links im Portal oder [Text](https://adresse) für externe Seiten.">
            <div data-vdbs-repeater data-vdbs-repeater-next="{{ $nextIndex }}">
                <div data-vdbs-repeater-items>
                    @foreach ($rows as $i => $row)
                        <x-faq-admin.entry-row :i="$i" :row="$row" />
                    @endforeach
                </div>

                <template data-vdbs-repeater-template>
                    <x-faq-admin.entry-row i="__INDEX__" />
                </template>

                <x-vdbs.actions.button type="button" variant="secondary" size="m" class="vdbs-js-only" data-vdbs-repeater-add icon="plus">Weitere Frage hinzufügen</x-vdbs.actions.button>
            </div>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('faq-admin.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <form method="post" action="{{ route('faq-admin.destroy', $category) }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Diese Kategorie mit allen Fragen löschen? Kontaktthemen, die darauf verweisen, haben danach kein Ziel mehr.')">Kategorie löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
