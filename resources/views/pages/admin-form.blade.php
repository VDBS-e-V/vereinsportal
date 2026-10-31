@php
    $key = $page->pageKey();
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seiten', 'href' => route('pages.admin.index')], ['label' => $key->title()]];
@endphp

<x-layouts.app :title="$key->title().' bearbeiten'" width="medium">
    <x-vdbs.layout.page-header :title="$key->title().' bearbeiten'" :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('pages.show', $key->value)" icon="eye">Ansehen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($page->isDefault())
        <x-vdbs.feedback.alert variant="warning">Bisher gilt der Standardtext. Mit dem Speichern wird er zum veröffentlichten Text.</x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="route('pages.admin.update', $key->value)" method="put">
        <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="60000" :rows="22" :value="$page->body" hint="Einfaches Markdown: Leerzeile = neuer Absatz, ## Überschrift, **fett**, *kursiv*, - Liste, [Text](https://…). Zeilenumbruch: zwei Leerzeichen am Zeilenende." />

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('pages.admin.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
