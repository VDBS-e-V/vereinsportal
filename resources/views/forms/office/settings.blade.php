@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Anträge', 'href' => route('forms-office.index')], ['label' => 'Einstellungen']])
<x-layouts.app title="Einstellungen der Anträge" width="medium">
    <x-vdbs.layout.page-header title="Einstellungen der Anträge" lead="Grenzen und Sätze. Änderungen gelten für neu eingereichte Anträge." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('forms-office.settings.update')" method="put">
        <x-vdbs.forms.form-section title="Freigabe">
            <x-vdbs.forms.input name="approval_limit" label="Freigabe allein durch die Geschäftsstelle bis (Euro)" :required="true" inputmode="decimal" :value="old('approval_limit', \App\Modules\Seminars\Support\Money::toInput($values['approval_limit_cents']))" hint="Darüber gibt zusätzlich die Geschäftsführung oder der Vorstand frei. Es gibt keinen Höchstbetrag." />
        </x-vdbs.forms.form-section>
        <x-vdbs.forms.form-section title="Fahrtkosten">
            <x-vdbs.forms.input name="km_rate" label="Kilometersatz (Euro je km)" :required="true" inputmode="decimal" :value="old('km_rate', \App\Modules\Seminars\Support\Money::toInput($values['km_rate_cents']))" />
            <x-vdbs.forms.input name="max_km_per_trip" type="number" label="Warnung ab Kilometern je Fahrt" :required="true" min="1" :value="old('max_km_per_trip', $values['max_km_per_trip'])" />
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
