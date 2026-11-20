@php
    use App\Modules\Quotes\Enums\QuoteStatus;
    use App\Support\Money;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare', 'href' => route('seminars.office.index')], ['label' => $seminar->number, 'href' => route('seminars.office.show', $seminar)], ['label' => 'Kostenvoranschlag']];
    $editable = $quote->status->isEditable();
    $lines = $quote->lines->values();
@endphp

<x-layouts.app title="Kostenvoranschlag" width="wide">
    <x-vdbs.layout.page-header :title="'Kostenvoranschlag, Version '.$quote->version" :lead="$seminar->number.' · '.$seminar->title.' · '.$seminar->partner->name" :breadcrumb="$crumbs" />

    @error('quote')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <p><x-vdbs.data.badge :tone="$quote->status->tone()">{{ $quote->status->label() }}</x-vdbs.data.badge>
        <a href="{{ route('quotes.pdf', $quote) }}">PDF ansehen</a></p>

    @if ($editable && $items->isNotEmpty())
        <x-vdbs.forms.form :action="route('quotes.office.add-item', [$seminar, $quote])" :summary="false" style="margin-bottom:var(--space-6)">
            <x-vdbs.forms.select name="item" label="Leistung aus der Preisliste übernehmen" placeholder="Bitte wählen" :options="$items->mapWithKeys(fn ($item): array => [$item->id => $item->name.' – '.Money::format($item->unit_price_cents).($item->unit ? ' / '.$item->unit : '')])->all()" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" size="s" icon="plus">Als Position einfügen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @if ($editable)
        <x-vdbs.forms.form :action="route('quotes.office.update', [$seminar, $quote])" method="put">
            <x-vdbs.forms.form-section title="Texte und Steuer">
                <x-vdbs.forms.textarea name="intro" label="Einleitung" :optional="true" maxlength="5000" :value="old('intro', $quote->intro)" />
                <x-vdbs.forms.input name="vat_percent" label="Umsatzsteuer in Prozent" type="number" :optional="true" :value="old('vat_percent', $quote->vat_percent)" hint="0, wenn der Verein für diese Leistung keine Umsatzsteuer ausweist." />
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Positionen">
                @for ($i = 0; $i < $rows; $i++)
                    @php $line = $lines->get($i); @endphp
                    <x-vdbs.forms.form-grid :columns="4">
                        <x-vdbs.forms.input :id="'l-'.$i.'-d'" :name="'lines['.$i.'][description]'" label="Position" :optional="true" maxlength="300" :value="old('lines.'.$i.'.description', $line?->description)" />
                        <x-vdbs.forms.input :id="'l-'.$i.'-q'" :name="'lines['.$i.'][quantity]'" label="Menge" :optional="true" inputmode="decimal" :value="old('lines.'.$i.'.quantity', $line ? rtrim(rtrim(number_format((float) $line->quantity, 2, ',', ''), '0'), ',') : null)" />
                        <x-vdbs.forms.input :id="'l-'.$i.'-u'" :name="'lines['.$i.'][unit]'" label="Einheit" :optional="true" maxlength="30" :value="old('lines.'.$i.'.unit', $line?->unit)" />
                        <x-vdbs.forms.input :id="'l-'.$i.'-p'" :name="'lines['.$i.'][price]'" label="Einzelpreis in Euro" :optional="true" inputmode="decimal" :value="old('lines.'.$i.'.price', $line ? Money::toInput($line->unit_price_cents) : null)" />
                    </x-vdbs.forms.form-grid>
                @endfor
            </x-vdbs.forms.form-section>

            <x-vdbs.forms.form-section title="Schluss">
                <x-vdbs.forms.textarea name="outro" label="Schlusstext" :optional="true" maxlength="5000" :value="old('outro', $quote->outro)" />
            </x-vdbs.forms.form-section>

            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Entwurf speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @else
        <x-vdbs.feedback.alert variant="info">Diese Version ist gesendet und lässt sich nicht mehr ändern. Auf der Seminarseite legen Sie bei Bedarf eine neue Version an.</x-vdbs.feedback.alert>
    @endif

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Summen</h2>
    <x-vdbs.data.detail-list :columns="3" layout="inline">
        <x-vdbs.data.detail-item label="Netto">{{ Money::format($quote->net_cents) }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item :label="'Umsatzsteuer ('.rtrim(rtrim((string) $quote->vat_percent, '0'), '.').' %)'">{{ Money::format($quote->vat_cents) }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gesamt">{{ Money::format($quote->total_cents) }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @if ($quote->status === QuoteStatus::Draft)
        @can('seminars.quote_approve')
            <x-vdbs.forms.form :action="route('quotes.approval.approve', $quote)" :summary="false" style="margin-top:var(--space-6)">
                <p>Die interne Freigabe gibt den Kostenvoranschlag zum Senden frei. Sie gilt nur für den jetzigen Stand der Positionen.</p>
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Intern freigeben</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @else
            <x-vdbs.feedback.alert variant="info">Der Entwurf muss zuerst von Vorstand oder Koordination intern freigegeben werden (Seite „KVA-Freigabe“).</x-vdbs.feedback.alert>
        @endcan
    @elseif ($quote->status === QuoteStatus::Approved)
        <x-vdbs.forms.form :action="route('quotes.office.send', [$seminar, $quote])" :summary="false" style="margin-top:var(--space-6)">
            <p>Intern freigegeben{{ $quote->approvedBy ? ' von '.($quote->approvedBy->person?->fullName() ?: $quote->approvedBy->email) : '' }} am {{ $quote->approved_at?->format('d.m.Y') }}. Der Kostenvoranschlag geht als E-Mail mit PDF an {{ $seminar->contact_email }}.</p>
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="mail">An die Schule senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif
</x-layouts.app>
