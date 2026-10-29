@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Preise', 'href' => route('seminar-prices.index')], ['label' => 'Neue Fassung']];
@endphp

<x-layouts.app title="Neue Preisliste" width="medium">
    <x-vdbs.layout.page-header title="Neue Fassung der Preisliste" lead="Die Preise der aktuellen Fassung sind eingetragen. Bestehende Kostenvoranschläge ändern sich nicht." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('seminar-prices.store')">
        <x-vdbs.forms.form-section title="Fassung">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="label" label="Bezeichnung" :required="true" :value="$values['label'] ?? null" hint="z. B. Preisstand Januar 2027" />
                <x-vdbs.forms.input name="valid_from" label="Gültig ab" type="date" :required="true" :value="$values['valid_from'] ?? null" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Preise in Euro">
            <x-vdbs.forms.form-grid :columns="2">
                @foreach ($items as $key => [$label, $note])
                    <x-vdbs.forms.input :name="'price_'.$key" :label="$label" :required="true" :value="$values[$key] ?? null" :hint="$note" suffix="€" />
                @endforeach
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Neue Fassung speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('seminar-prices.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
