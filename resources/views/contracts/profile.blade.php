@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Vertragsdaten']];
@endphp

<x-layouts.app title="Vertragsdaten" width="wide">
    <x-vdbs.layout.page-header title="Meine Vertragsdaten" lead="Mit diesen Angaben füllt das Portal Ihre Dienstleistungsverträge vor. Sie sind freiwillig. Steuernummer und IBAN werden verschlüsselt gespeichert und nur für Verträge genutzt; die Koordination sieht sie nur am einzelnen Vertrag. Sie können sie jederzeit löschen." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('contractor.update')" method="put">
        <x-vdbs.forms.form-section title="Steuer">
            <x-vdbs.forms.input name="tax_number" label="Steuernummer" :optional="true" maxlength="30" :value="$profile?->tax_number" />
            <x-vdbs.forms.radio-group name="vat_mode" legend="Umsatzsteuer" :optional="true" :options="['19' => '19 %', '7' => '7 %', 'none' => 'ohne Umsatzsteuer']" :value="$profile?->vat_mode" />
        </x-vdbs.forms.form-section>
        <x-vdbs.forms.form-section title="Bankverbindung">
            <x-vdbs.forms.input name="account_holder" label="Kontoinhaber:in" :optional="true" maxlength="150" :value="$profile?->account_holder" />
            <x-vdbs.forms.input name="bank_name" label="Bankinstitut" :optional="true" maxlength="120" :value="$profile?->bank_name" />
            <x-vdbs.forms.input name="bic" label="BIC" :optional="true" maxlength="20" :value="$profile?->bic" />
            <x-vdbs.forms.input name="iban" label="IBAN" :optional="true" maxlength="40" :value="$profile?->iban" />
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>

    @if ($profile)
        <form method="post" action="{{ route('contractor.destroy') }}" style="margin-top:var(--space-5)">@csrf @method('delete')
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2">Meine Vertragsdaten löschen</x-vdbs.actions.button>
        </form>
    @endif
</x-layouts.app>
