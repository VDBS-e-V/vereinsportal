@php
    use App\Support\Money;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare', 'href' => route('seminars.office.index')], ['label' => 'Preisliste']];
@endphp

<x-layouts.app title="Preisliste" width="wide">
    <x-vdbs.layout.page-header title="Preisliste und Textbausteine" lead="Leistungen, die Sie in einen Kostenvoranschlag übernehmen können. Positionen in bestehenden Kostenvoranschlägen ändern sich nicht, wenn Sie hier etwas ändern." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('quotes.office.prices.update')" method="put">
        <x-vdbs.forms.form-section title="Leistungen">
            @for ($i = 0; $i < $rows; $i++)
                @php $item = $items->get($i); @endphp
                <x-vdbs.forms.form-grid :columns="4">
                    <x-vdbs.forms.input :id="'i-'.$i.'-n'" :name="'items['.$i.'][name]'" label="Leistung" :optional="true" maxlength="150" :value="old('items.'.$i.'.name', $item?->name)" />
                    <x-vdbs.forms.input :id="'i-'.$i.'-u'" :name="'items['.$i.'][unit]'" label="Einheit" :optional="true" maxlength="30" :value="old('items.'.$i.'.unit', $item?->unit)" hint="Zum Beispiel Tag oder Stunde." />
                    <x-vdbs.forms.input :id="'i-'.$i.'-p'" :name="'items['.$i.'][price]'" label="Preis in Euro" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.price', $item ? Money::toInput($item->unit_price_cents) : null)" />
                    <x-vdbs.forms.select :id="'i-'.$i.'-a'" :name="'items['.$i.'][active]'" label="Status" :options="['1' => 'aktiv', '0' => 'ausgeblendet']" :value="old('items.'.$i.'.active', ($item?->active ?? true) ? '1' : '0')" />
                </x-vdbs.forms.form-grid>
            @endfor
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Textbausteine">
            <x-vdbs.forms.textarea name="texts[intro]" label="Einleitung (Standard für neue Kostenvoranschläge)" :optional="true" maxlength="5000" :value="old('texts.intro', $texts['intro'] ?? '')" />
            <x-vdbs.forms.textarea name="texts[outro]" label="Schlusstext (Standard für neue Kostenvoranschläge)" :optional="true" maxlength="5000" :value="old('texts.outro', $texts['outro'] ?? '')" />
        </x-vdbs.forms.form-section>

        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
