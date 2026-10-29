@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Kostenvoranschlag']];
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format($cents);
    $days = (int) ($values['days'] ?? 1);
@endphp

<x-layouts.app title="Kostenvoranschlag berechnen" width="wide">
    <x-vdbs.layout.page-header title="Kostenvoranschlag berechnen" :lead="$seminar->title.' · '.$seminar->type->label().' · Fassung '.$version" :breadcrumb="$crumbs" />

    <x-vdbs.layout.grid :columns="2" align="start">
        <x-vdbs.forms.form :action="route('seminars.quotes.store', $seminar)">
            <x-vdbs.forms.form-section title="Angaben" description="Die Voreinstellungen richten sich nach der Leistung und lassen sich ändern. „Berechnen“ zeigt die Vorschau, „Speichern“ legt den Kostenvoranschlag fest.">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input name="days" :label="$labels['days']" type="number" min="1" max="30" :required="true" :value="$values['days'] ?? null" />
                    <x-vdbs.forms.input name="team" :label="$labels['team']" type="number" min="1" max="30" :required="true" :value="$values['team'] ?? null" />
                    <x-vdbs.forms.input name="hours" :label="$labels['hours']" type="number" min="0.5" max="14" step="0.5" :required="true" :value="$values['hours'] ?? null" />
                    <x-vdbs.forms.input name="leaders" :label="$labels['leaders']" type="number" min="0" max="10" :required="true" :value="$values['leaders'] ?? null" />
                    <x-vdbs.forms.input name="group" :label="$labels['group']" type="number" min="1" max="2000" :required="true" :value="$values['group'] ?? null" />
                    <x-vdbs.forms.input name="material_factor" :label="$labels['material_factor']" type="number" min="0" max="5" step="0.1" :required="true" :value="$values['material_factor'] ?? null" hint="1 = volle Pauschale, 0,3 = Tagestreffen" />
                    <x-vdbs.forms.input name="material_trips" :label="$labels['material_trips']" type="number" min="0" max="20" :required="true" :value="$values['material_trips'] ?? 0" />
                    <x-vdbs.forms.input name="boards" :label="$labels['boards']" type="number" min="0" max="20" :required="true" :value="$values['boards'] ?? 0" />
                </x-vdbs.forms.form-grid>
                <x-vdbs.forms.checkbox name="inside_abc" label="Innerhalb des Tarifbereichs ABC (Berlin)" :checked="(bool) ($values['inside_abc'] ?? false)" />
                <x-vdbs.forms.checkbox name="without_club_fee" label="Ohne Vereinspauschale" :checked="(bool) ($values['without_club_fee'] ?? false)" />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Darstellung im Schreiben" description="So steht der Posten im gedruckten Kostenvoranschlag. Eine Zeile je Aufzählungspunkt; leer lassen für den Standardtext.">
                <x-vdbs.forms.input name="print_title" label="Posten" :optional="true" maxlength="150" :value="$values['print_title'] ?? null" />
                <x-vdbs.forms.textarea name="print_content" label="Inhalt" :optional="true" maxlength="1000" :rows="4" :value="$values['print_content'] ?? null" />
            </x-vdbs.forms.form-section>

            <x-slot:actions>
                <x-vdbs.actions.button type="submit" name="action" value="preview" variant="secondary" icon="refresh-cw">Berechnen</x-vdbs.actions.button>
                <x-vdbs.actions.button type="submit" name="action" value="save" icon="save">Kostenvoranschlag speichern</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="tertiary" :href="route('seminars.show', $seminar)">Abbrechen</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>

        <section aria-labelledby="preview-heading">
            <h2 id="preview-heading" style="font-size:var(--font-size-h3)">Vorschau</h2>
            @if ($preview === null)
                <p>Wählen Sie „Berechnen“, um die Posten zu sehen.</p>
            @else
                @include('seminars._lines', ['lines' => $preview['lines'], 'total' => $preview['total_cents'], 'days' => $preview['inputs']['days']])
                <p style="color:var(--text-muted);font-size:var(--font-size-small)">Preisstand: {{ $preview['price_list']->label }} (gültig ab {{ $preview['price_list']->valid_from->format('d.m.Y') }}). Noch nicht gespeichert.</p>
            @endif
        </section>
    </x-vdbs.layout.grid>
</x-layouts.app>
