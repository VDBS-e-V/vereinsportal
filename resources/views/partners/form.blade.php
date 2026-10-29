@php
    $editing = $partner !== null;
    $heading = $editing ? 'Schule bearbeiten' : 'Schule anlegen';
    $crumbs = array_values(array_filter([
        ['label' => 'Start', 'href' => route('home')],
        ['label' => 'Koordination'],
        ['label' => 'Schulen', 'href' => route('partners.index')],
        $editing ? ['label' => $partner->name, 'href' => route('partners.show', $partner)] : null,
        ['label' => $editing ? 'Bearbeiten' : 'Anlegen'],
    ]));
@endphp

<x-layouts.app :title="$heading" width="medium">
    <x-vdbs.layout.page-header :title="$heading" :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('partners.update', $partner) : route('partners.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Schule">
            <x-vdbs.forms.input name="name" label="Name der Schule" :required="true" :value="$values['name'] ?? null" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="legal_name" label="Träger oder offizieller Name" :optional="true" :value="$values['legal_name'] ?? null" />
                <x-vdbs.forms.input name="school_number" label="Schulnummer" :optional="true" :value="$values['school_number'] ?? null" hint="z. B. 08K06. Mit dem Bundesland ergibt sich die interne Kennung, z. B. BE/08K06." />
                <x-vdbs.forms.select name="school_type" label="Schulart" placeholder="Bitte wählen" :optional="true" :options="$typeOptions" :value="$values['school_type'] ?? null" />
                <x-vdbs.forms.select name="federal_state" label="Bundesland" placeholder="Bitte wählen" :optional="true" :options="$stateOptions" :value="$values['federal_state'] ?? null" />
                <x-vdbs.forms.input name="student_count" label="Schülerzahl" type="number" min="0" max="20000" :optional="true" :value="$values['student_count'] ?? null" />
                <x-vdbs.forms.select name="status" label="Status" :required="true" :options="$statusOptions" :value="$values['status'] ?? 'active'" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Anschrift">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="street" label="Straße" :optional="true" :value="$values['street'] ?? null" />
                <x-vdbs.forms.input name="house_number" label="Hausnummer" :optional="true" :value="$values['house_number'] ?? null" />
                <x-vdbs.forms.input name="postal_code" label="Postleitzahl" :optional="true" :value="$values['postal_code'] ?? null" />
                <x-vdbs.forms.input name="city" label="Ort" :optional="true" :value="$values['city'] ?? null" />
                <x-vdbs.forms.input name="country_code" label="Land (Code)" :required="true" maxlength="2" :value="$values['country_code'] ?? 'DE'" hint="Zweistelliger Code, z. B. DE, AT oder CH" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Ansprechpersonen" description="Beliebig viele Personen. Wählen Sie eine als Hauptansprechperson. Leere Zeilen werden ignoriert; zum Entfernen die Zeile leeren oder entfernen.">
            @php
                $contactRows = array_values((array) old('contacts', $values['contacts'] ?? []));
                $contactKeys = array_keys((array) old('contacts', $values['contacts'] ?? []));
                $primary = (string) old('primary_contact', $values['primary_contact'] ?? 0);
                // Ohne JavaScript immer zwei leere Zeilen mehr, damit man auch dann weitere Personen anlegen kann
                $rows = [];
                foreach ($contactRows as $n => $row) { $rows[$contactKeys[$n]] = $row; }
                $next = $rows === [] ? 0 : (max(array_map('intval', array_keys($rows))) + 1);
                foreach (range(0, 1) as $extra) { $rows[$next + $extra] = []; }
                $nextIndex = max(array_map('intval', array_keys($rows))) + 1;
            @endphp

            <div data-vdbs-repeater data-vdbs-repeater-next="{{ $nextIndex }}">
                <div data-vdbs-repeater-items>
                    @foreach ($rows as $i => $row)
                        <x-partners.contact-row :i="$i" :row="$row" :primary="(string) $i === $primary || ($primary === '' && $loop->first)" />
                    @endforeach
                </div>

                <template data-vdbs-repeater-template>
                    <x-partners.contact-row i="__INDEX__" />
                </template>

                <button type="button" class="vdbs-btn vdbs-btn--secondary vdbs-btn--shape-outline vdbs-btn--size-m vdbs-js-only" data-vdbs-repeater-add>
                    <x-vdbs.icon name="plus" :size="18" /> Weitere Ansprechperson hinzufügen
                </button>
            </div>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Notizen">
            <x-vdbs.forms.textarea name="notes" label="Notiz" :optional="true" maxlength="2000" :counter="true" :rows="4" :value="$values['notes'] ?? null" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">{{ $editing ? 'Speichern' : 'Schule anlegen' }}</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="$editing ? route('partners.show', $partner) : route('partners.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
