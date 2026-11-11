@php($data = (array) $application->data)
<x-vdbs.forms.form-section title="Was soll angeschafft werden?">
    @for ($i = 0; $i < $rows; $i++)
        @php($item = $items[$i] ?? null)
        @php($extra = $item ? (array) $item->extra : [])
        <fieldset style="margin-bottom:var(--space-5);padding:var(--space-4);border:1px solid var(--color-border,#ccc)">
            <legend>Artikel {{ $i + 1 }}@if ($item && $item->amount_cents > 0) · {{ number_format($item->amount_cents / 100, 2, ',', '.') }} €@endif</legend>
            @if ($item) <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}"> @endif
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input :id="'item-'.$i.'-description'" :name="'items['.$i.'][description]'" label="Bezeichnung" :optional="true" maxlength="200" :value="old('items.'.$i.'.description', $item?->description)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-supplier'" :name="'items['.$i.'][supplier]'" label="Lieferant" :optional="true" maxlength="150" :value="old('items.'.$i.'.supplier', $extra['supplier'] ?? null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-quantity'" :name="'items['.$i.'][quantity]'" label="Menge" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.quantity', $item ? rtrim(rtrim(number_format($item->quantity, 2, ',', ''), '0'), ',') : null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-unit'" :name="'items['.$i.'][unit]'" label="Einzelpreis brutto in Euro" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.unit', $item ? \App\Modules\Seminars\Support\Money::toInput($item->unit_cents) : null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-link'" :name="'items['.$i.'][link]'" label="Link oder Artikelnummer" :optional="true" maxlength="300" :value="old('items.'.$i.'.link', $extra['link'] ?? null)" />
            </x-vdbs.forms.form-grid>
            @if ($item && $item->files->isNotEmpty())
                <p style="margin:var(--space-2) 0">Angebot: @foreach ($item->files as $file)<a href="{{ route($audience.'-forms.file', [$application, $file]) }}">{{ $file->original_name }}</a>@if (! $loop->last), @endif @endforeach</p>
            @endif
            <label for="file-{{ $i }}" style="display:block;font-weight:600">Angebot anhängen (freiwillig)</label>
            <input id="file-{{ $i }}" type="file" name="files[{{ $i }}]" accept="application/pdf,image/jpeg,image/png">
        </fieldset>
    @endfor
    <x-vdbs.actions.button type="submit" name="action" value="add_row" variant="secondary" icon="plus">Weiterer Artikel</x-vdbs.actions.button>
</x-vdbs.forms.form-section>

<x-vdbs.forms.form-section title="Lieferung">
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.select name="urgency" label="Dringlichkeit" :options="['normal' => 'normal', 'urgent' => 'dringend']" :value="old('urgency', $data['urgency'] ?? 'normal')" />
        <x-vdbs.forms.input name="wanted_date" type="date" label="Gewünscht bis" :optional="true" :value="old('wanted_date', $data['wanted_date'] ?? null)" />
        <x-vdbs.forms.select name="delivery" label="Lieferung an" :options="['office' => 'Geschäftsstelle', 'other' => 'Andere Adresse']" :value="old('delivery', $data['delivery'] ?? 'office')" />
        <x-vdbs.forms.input name="delivery_contact" label="Ansprechperson vor Ort" :optional="true" maxlength="150" :value="old('delivery_contact', $data['delivery_contact'] ?? null)" />
    </x-vdbs.forms.form-grid>
    <x-vdbs.forms.textarea name="delivery_address" label="Lieferadresse (bei anderer Adresse)" :optional="true" :rows="2" maxlength="300" :value="old('delivery_address', $data['delivery_address'] ?? null)" />
</x-vdbs.forms.form-section>
