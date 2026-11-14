@php
    $editing = $topic->exists;
    $title = $editing ? 'Thema bearbeiten' : ($topic->action === 'category' ? 'Neuer Bereich' : 'Neues Thema');
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Kontaktformular', 'href' => route('contact-admin.topics.index')], ['label' => $title]];
    $current = old('action', $topic->action);
@endphp

<x-layouts.app :title="$title" width="wide">
    <x-vdbs.layout.page-header :title="$title" lead="Ein Thema führt zu einem Formular, zum Standardformular, zu einer FAQ-Seite oder zu einer anderen Seite. Bereiche fassen Themen zusammen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('contact-admin.topics.update', $topic) : route('contact-admin.topics.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Thema">
            <x-vdbs.forms.input name="title" label="Bezeichnung" :required="true" maxlength="150" :value="$topic->title" hint="So steht es in der Themenwahl, z. B. „Passwort vergessen“." />
            <x-vdbs.forms.input name="description" label="Kurzbeschreibung" :optional="true" maxlength="250" :value="$topic->description" />
            <x-vdbs.forms.input name="keywords" label="Stichwörter für die Suche" :optional="true" maxlength="500" :value="$topic->keywords" hint="Wörter, nach denen Besucherinnen und Besucher suchen könnten, durch Leerzeichen getrennt." />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="parent_id" label="Liegt im Bereich" :optional="true" placeholder="-- ganz oben --" :options="$categories" :value="$topic->parent_id" />
                <x-vdbs.forms.input name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="$topic->sort_order" hint="Kleine Zahlen stehen oben." />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.checkbox name="is_active" label="In der Themenwahl sichtbar" :checked="(bool) old('is_active', $topic->is_active)" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Wohin führt das Thema?">
            <x-vdbs.forms.select name="action" label="Art" :required="true" :options="\App\Modules\ContactForms\Models\ContactTopic::ACTIONS" :value="$topic->action" :hint="$hasChildren ? 'Dieser Bereich enthält Themen und bleibt ein Bereich.' : null" />
            <x-vdbs.forms.select name="faq_category_id" label="FAQ-Kategorie" :optional="true" placeholder="-- bitte wählen --" :options="$faqCategories" :value="$topic->faq_category_id" hint="Für „Führt zu einer FAQ-Seite“: Die Fragen dieser Kategorie erscheinen auf der Themenseite. Fragen und Kategorien pflegen Sie unter Geschäftsstelle › FAQ." />
            <x-vdbs.forms.select name="contact_form_id" label="Formular" :optional="true" placeholder="-- bitte wählen --" :options="$forms" :value="$topic->contact_form_id" hint="Für „Führt zu einem Formular“. Bei einer FAQ-Seite: das Formular für Rückfragen (sonst das Standardformular)." />
            <x-vdbs.forms.input name="target_url" label="Link" :optional="true" maxlength="500" :value="$topic->target_url" hint="Für „Führt zu einer anderen Seite“: ein Pfad im Portal wie /zugang-zum-portal oder eine Adresse mit https://." />
            <x-vdbs.forms.checkbox name="faq_offer_form" label="Auf der FAQ-Seite „Uns schreiben“ anbieten" :checked="(bool) old('faq_offer_form', $topic->faq_offer_form)" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('contact-admin.topics.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <form method="post" action="{{ route('contact-admin.topics.destroy', $topic) }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2" onclick="return confirm('Dieses Thema löschen?')">Thema löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
