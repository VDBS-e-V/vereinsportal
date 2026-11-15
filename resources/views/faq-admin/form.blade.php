@php
    $editing = $category->exists;
    $title = $editing ? 'Kategorie bearbeiten' : 'Neue Kategorie';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'FAQ', 'href' => route('faq-admin.index')], ['label' => $title]];
@endphp

<x-layouts.app :title="$title" width="medium">
    <x-vdbs.layout.page-header :title="$title" lead="Eine Kategorie fasst zusammen, was zusammengehört (z. B. „Zugang beantragen“). Kontaktthemen können auf eine Kategorie verweisen." :breadcrumb="$crumbs">
        @if ($editing)
            <x-slot:actions>
                <x-vdbs.actions.button :href="route('faq-admin.entries.index', $category)" variant="secondary" icon="pencil">Fragen bearbeiten</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="$editing ? route('faq-admin.update', $category) : route('faq-admin.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Kategorie">
            <x-vdbs.forms.input name="title" label="Bezeichnung" :required="true" maxlength="150" :value="$category->title" />
            <x-vdbs.forms.input name="description" label="Kurzbeschreibung" :optional="true" maxlength="250" :value="$category->description" hint="Steht unter der Überschrift auf der FAQ-Seite." />
            <x-vdbs.forms.input name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="$category->sort_order" hint="Kleine Zahlen stehen oben." />
            <x-vdbs.forms.checkbox name="is_active" label="Sichtbar" :checked="(bool) old('is_active', $category->is_active)" hint="Ausgeblendete Kategorien erscheinen nirgends, auch nicht über Kontaktthemen." />
            <x-vdbs.forms.checkbox name="in_faq_page" label="Auf der großen FAQ-Seite zeigen" :checked="(bool) old('in_faq_page', $category->in_faq_page)" hint="Ohne Häkchen erscheinen die Fragen nur dort, wo ein Kontaktthema auf diese Kategorie verweist." />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">{{ $editing ? 'Speichern' : 'Anlegen und Fragen erfassen' }}</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('faq-admin.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <form method="post" action="{{ route('faq-admin.destroy', $category) }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Diese Kategorie mit allen Fragen löschen? Kontaktthemen, die darauf verweisen, haben danach kein Ziel mehr.')">Kategorie löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
