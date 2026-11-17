@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Web-Control'], ['label' => 'Header', 'href' => route('navigation.headers')], ['label' => $area->label()]];
@endphp

<x-layouts.app title="Header bearbeiten" width="wide">
    <x-vdbs.layout.page-header :title="'Header bearbeiten „'.$area->label().'“'" lead="Gestalten Sie den Header des Bereichs: Fügen Sie Seiten und eigene Links hinzu, ändern Sie Titel und blenden Sie Seiten aus. Die Reihenfolge der Spalten ist die waagerechte Position im Kopfmenü, die Reihenfolge innerhalb einer Spalte die senkrechte Position im Untermenü." :breadcrumb="$crumbs" />

    <noscript><x-vdbs.feedback.alert variant="warning">Der Editor braucht JavaScript. Bitte aktivieren Sie es in Ihrem Browser.</x-vdbs.feedback.alert></noscript>

    <x-vdbs.forms.form :action="route('navigation.header.update', $area->value)" method="put">
        @error('layout')<x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>@enderror
        <input type="hidden" name="layout" value="" data-nav-layout-input>
        <script type="application/json" data-nav-layout-data>@json($layout)</script>

        <section aria-labelledby="nav-preview-heading">
            <h2 id="nav-preview-heading" class="vdbs-nav-editor__heading">Vorschau</h2>
            <nav class="vdbs-nav-editor__preview" aria-label="Vorschau des Headers" data-nav-preview></nav>
        </section>

        <section aria-labelledby="nav-editor-heading">
            <h2 id="nav-editor-heading" class="vdbs-nav-editor__heading">Aufbau</h2>
            <div class="vdbs-nav-editor" data-nav-editor></div>
            <p><x-vdbs.actions.button type="button" variant="secondary" icon="plus" data-nav-add>Neue Hauptseite</x-vdbs.actions.button></p>
        </section>

        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
