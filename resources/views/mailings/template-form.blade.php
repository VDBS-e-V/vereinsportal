@php
    $editing = $template !== null;
    $pageTitle = $editing ? 'Vorlage bearbeiten' : 'Neue Vorlage';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Mailvorlagen', 'href' => route('mailings.templates.index')], ['label' => $editing ? 'Vorlage bearbeiten' : 'Neue Vorlage']];
@endphp

<x-layouts.app :title="$pageTitle" width="wide">
    <x-vdbs.layout.page-header :title="$editing ? 'Vorlage bearbeiten' : 'Neue Vorlage'" lead="Betreff und Text mit Platzhaltern. Beim Schreiben eines Rundschreibens lässt sich die Vorlage übernehmen und frei ändern." :breadcrumb="$crumbs">
        @if ($editing)
            <x-slot:actions>
                <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="delete-template">Löschen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.form :action="$editing ? route('mailings.templates.update', $template) : route('mailings.templates.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Vorlage">
            <x-vdbs.forms.input name="name" label="Name der Vorlage" :required="true" maxlength="120" :value="$template?->name" hint="Unter diesem Namen erscheint sie beim Schreiben, z. B. Einladung zur Mitgliederversammlung" />
            <x-vdbs.forms.input name="description" label="Beschreibung" :optional="true" maxlength="250" :value="$template?->description" />
            <x-vdbs.forms.input name="subject" label="Betreff" :required="true" maxlength="200" :value="$template?->subject" />
            <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="20000" :rows="14" :rich="true" :value="$body" />
            <x-mailings.placeholders :placeholders="$placeholders" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('mailings.templates.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    @if ($editing)
        <x-vdbs.overlays.confirm-dialog id="delete-template" title="Vorlage löschen?" variant="danger" :action="route('mailings.templates.destroy', $template)" method="delete" confirm-label="Vorlage löschen">
            <p>Die Vorlage <strong>{{ $template->name }}</strong> wird gelöscht. Bereits gesendete Schreiben bleiben unverändert.</p>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-layouts.app>
