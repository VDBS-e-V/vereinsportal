@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Verträge']];
@endphp

<x-layouts.app title="Verträge" width="wide">
    <x-vdbs.layout.page-header title="Verträge" lead="Vertragstext und Vereinsdaten für die Dienstleistungsverträge der Teamenden. Die Verträge selbst erzeugen Sie auf der Seite des Seminars." :breadcrumb="$crumbs" />

    <x-vdbs.layout.section title="Vereinsdaten">
        <x-vdbs.forms.form :action="route('contracts.admin.settings')" method="put">
            @foreach ($labels as $key => $label)
                <x-vdbs.forms.input :name="$key" :label="$label" :required="true" maxlength="300" :value="$settings[$key]" />
            @endforeach
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Vereinsdaten speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </x-vdbs.layout.section>

    <section aria-labelledby="text-title" style="margin-top:var(--space-8)">
        <h2 id="text-title" style="font-size:var(--font-size-h3)">Vertragstext, Version {{ $shown->version }}@if ($shown->id === $active->id) (gültig)@endif</h2>
        <x-vdbs.feedback.alert variant="info">Der Standardtext entspricht der Vertragsvorlage „Dienstleistungsvertrag“ (Stand 08.07.2024). Änderungen am Wortlaut sollten rechtlich geprüft werden.</x-vdbs.feedback.alert>

        @if ($canTemplate)
            <x-vdbs.forms.form :action="route('contracts.admin.version')" method="post">
                <x-vdbs.forms.input name="title" label="Titel" :required="true" maxlength="150" :value="$shown->title" />
                <x-vdbs.forms.textarea name="body" label="Text" :required="true" maxlength="60000" :rows="18" :rich="true" :value="$body" hint="Beim Speichern entsteht eine neue Version. Bestehende Verträge behalten ihre Fassung." />
                <ul>@foreach ($placeholders as $key => $meaning)<li><code>{{ '{'.$key.'}' }}</code> – {{ $meaning }}</li>@endforeach</ul>
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Als neue Version speichern</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @else
            <div class="vdbs-prose">{!! \App\Support\Html\RichText::clean($shown->body) !!}</div>
            <p class="vdbs-hint">Den Vertragstext ändert nur der Vorstand.</p>
        @endif
    </section>

    <section aria-labelledby="versions-title" style="margin-top:var(--space-8)">
        <h2 id="versions-title" style="font-size:var(--font-size-h3)">Versionen</h2>
        <ul>
            @foreach ($versions as $version)
                <li><a href="{{ route('contracts.admin.index', ['version' => $version->version]) }}">Version {{ $version->version }}</a> vom {{ $version->created_at?->format('d.m.Y') }}@if ($version->active) (gültig)@endif</li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
