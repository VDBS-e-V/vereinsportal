@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Vorlage Schulvertrag']];
@endphp

<x-layouts.app title="Vorlage Schulvertrag" width="wide">
    <x-vdbs.layout.page-header title="Vorlage für den Vertrag mit der Schule" lead="Jede Änderung ist eine neue Fassung. Bestehende Verträge behalten ihre Fassung. Platzhalter stehen in geschweiften Klammern." :breadcrumb="$crumbs" />

    <x-vdbs.forms.error-summary />

    <x-vdbs.forms.form :action="route('school-contracts.template.update')" method="put">
        <x-vdbs.forms.input id="tpl-title" name="title" label="Titel" :required="true" maxlength="150" :value="old('title', $shown->title)" />
        <x-vdbs.forms.textarea id="tpl-body" name="body" label="Vertragstext" :required="true" :rich="true" :rows="18" :value="old('body', $body)" />
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Als neue Fassung speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>

    <section aria-labelledby="ph-heading">
        <h2 id="ph-heading" style="font-size:var(--font-size-h3)">Platzhalter</h2>
        <ul>@foreach ($placeholders as $key => $label)<li><code>{{ '{'.$key.'}' }}</code>: {{ $label }}</li>@endforeach</ul>
    </section>

    <section aria-labelledby="ver-heading">
        <h2 id="ver-heading" style="font-size:var(--font-size-h3)">Fassungen</h2>
        <ul>@foreach ($versions as $version)<li><a href="{{ route('school-contracts.template', ['version' => $version->version]) }}">Fassung {{ $version->version }}</a>@if ($version->active) (gültig)@endif · {{ $version->created_at?->format('d.m.Y') }}</li>@endforeach</ul>
    </section>
</x-layouts.app>
