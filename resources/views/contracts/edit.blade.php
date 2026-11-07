@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.index')], ['label' => $seminar->title, 'href' => route('seminars.show', $seminar)], ['label' => 'Vertrag '.$contract->number]];
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::toInput((int) $cents);
@endphp

<x-layouts.app :title="'Vertrag '.$contract->number" width="wide">
    <x-vdbs.layout.page-header :title="'Vertrag '.$contract->number" lead="Prüfen Sie die Angaben vor dem Drucken. Leere Felder bleiben als Linie zum Ausfüllen von Hand. Steuernummer und IBAN sind verschlüsselt gespeichert und stehen nie in Listen oder im Protokoll." :breadcrumb="$crumbs" />

    @if ($contract->isEditable())
        <x-vdbs.forms.form :action="route('contracts.update', [$seminar, $contract])" method="put">
            <x-vdbs.forms.form-section title="Auftragnehmer:in">
                <x-vdbs.forms.input name="name" label="Name, Vorname" :optional="true" maxlength="190" :value="$data['name'] ?? null" />
                <x-vdbs.forms.input name="address" label="Anschrift" :optional="true" maxlength="300" :value="$data['address'] ?? null" />
                <x-vdbs.forms.input name="tax_number" label="Steuernummer" :optional="true" maxlength="30" :value="$data['tax_number'] ?? null" />
                <x-vdbs.forms.radio-group name="vat_mode" legend="Umsatzsteuer" :optional="true" :options="['19' => '19 %', '7' => '7 %', 'none' => 'ohne Umsatzsteuer']" :value="$data['vat_mode'] ?? null" hint="Ohne Auswahl bleiben die Kästchen leer, die Person kreuzt am Seminartag an." />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Leistung und Vergütung">
                <x-vdbs.forms.textarea name="service_text" label="Leistung" :optional="true" maxlength="2000" :rows="5" :value="$data['service_text'] ?? null" />
                <x-vdbs.forms.input name="amount" label="Vergütung (Euro)" :optional="true" maxlength="20" :value="$money($data['amount_cents'] ?? 0)" :hint="'Aus dem Kostenvoranschlag: '.$money($data['amount_original_cents'] ?? 0).' €.'.(($data['leader_hint_cents'] ?? 0) > 0 && ! ($contract->application?->is_lead) ? ' Für die Seminarleitung kommt laut Kostenvoranschlag '.$money($data['leader_hint_cents']).' € hinzu; rechnen Sie ihn bei Bedarf ein.' : '').' Bei einer Abweichung ist ein Vermerk nötig.'" />
                <x-vdbs.forms.input name="amount_note" label="Vermerk zur Abweichung" :optional="true" maxlength="300" :value="$data['amount_note'] ?? null" />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Bankverbindung">
                <x-vdbs.forms.input name="bank_name" label="Bankinstitut" :optional="true" maxlength="120" :value="$data['bank_name'] ?? null" />
                <x-vdbs.forms.input name="bic" label="BIC" :optional="true" maxlength="20" :value="$data['bic'] ?? null" />
                <x-vdbs.forms.input name="iban" label="IBAN" :optional="true" maxlength="40" :value="$data['iban'] ?? null" />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Ort und Datum">
                <x-vdbs.forms.input name="place" label="Ort" :optional="true" maxlength="100" :value="$data['place'] ?? null" />
                <x-vdbs.forms.input name="date" label="Datum" :optional="true" maxlength="30" :value="$data['date'] ?? null" />
                @if ($contract->user_id)
                    <x-vdbs.forms.checkbox name="remember" label="Steuernummer, Umsatzsteuer und Bankverbindung für künftige Verträge im Teamkonto speichern" />
                @endif
            </x-vdbs.forms.form-section>

            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('contracts.show', [$seminar, $contract])" icon="printer">Ansehen und drucken</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="text" :href="route('seminars.show', $seminar)">Zurück zum Seminar</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    @else
        <p>Der Vertrag ist {{ $contract->statusLabel() }} und lässt sich nicht mehr ändern.</p>
        <x-vdbs.actions.button variant="text" :href="route('seminars.show', $seminar)">Zurück zum Seminar</x-vdbs.actions.button>
    @endif
</x-layouts.app>
