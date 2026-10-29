@php
    $editing = $person !== null;
    $name = $editing ? $person->fullName() : null;
    $heading = $editing ? 'Person bearbeiten' : 'Person anlegen';
    $crumbs = array_values(array_filter([
        ['label' => 'Start', 'href' => route('home')],
        ['label' => 'Verwaltung'],
        ['label' => 'Personen', 'href' => route('admin.people.index')],
        $editing ? ['label' => $name, 'href' => route('admin.people.show', $person)] : null,
        ['label' => $editing ? 'Bearbeiten' : 'Anlegen'],
    ]));
    $birth = $person?->birth_date?->format('Y-m-d');
@endphp

<x-layouts.app :title="$heading" width="medium">
    <x-vdbs.layout.page-header :title="$heading" :breadcrumb="$crumbs"
        :lead="$editing ? null : 'Eine Person kann ohne Konto existieren. Ein Konto mit Einladung legen Sie danach auf der Detailseite an.'" />

    <x-vdbs.forms.form :action="$editing ? route('admin.people.update', $person) : route('admin.people.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Name und Geburtsdatum">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="title" label="Titel" :optional="true" :value="$person?->title" />
                <x-vdbs.forms.input name="name_addition" label="Namenszusatz" :optional="true" :value="$person?->name_addition" hint="z. B. von oder de" />
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" autocomplete="off" :value="$person?->first_name" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" autocomplete="off" :value="$person?->last_name" />
                <x-vdbs.forms.input name="birth_date" label="Geburtsdatum" type="date" :required="true" :value="$birth" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Kontakt">
            <x-vdbs.forms.form-grid :columns="2">
                @if ($hasAccount)
                    <x-vdbs.forms.input name="email_display" label="E-Mail-Adresse" type="email" :value="$person->email" disabled hint="Die Person hat ein Konto. Die Adresse ändert sie unter Konto > E-Mail." />
                @else
                    <x-vdbs.forms.input name="email" label="E-Mail-Adresse" type="email" :required="true" autocomplete="off" :value="$person?->email" />
                @endif
                <x-vdbs.forms.input name="phone" label="Telefon" type="tel" :optional="true" :value="$person?->phone" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Anschrift">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="street" label="Straße" :optional="true" :value="$person?->street" />
                <x-vdbs.forms.input name="house_number" label="Hausnummer" :optional="true" :value="$person?->house_number" />
                <x-vdbs.forms.input name="postal_code" label="Postleitzahl" :optional="true" :value="$person?->postal_code" />
                <x-vdbs.forms.input name="city" label="Ort" :optional="true" :value="$person?->city" />
                <x-vdbs.forms.input name="country_code" label="Land (Code)" :required="true" maxlength="2" :value="$person?->country_code ?? 'DE'" hint="Zweistelliger Code, z. B. DE, AT oder CH" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">{{ $editing ? 'Speichern' : 'Person anlegen' }}</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="$editing ? route('admin.people.show', $person) : route('admin.people.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
