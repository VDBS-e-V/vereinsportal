@php($modes = ['car' => 'PKW (eigenes Auto)', 'train' => 'Bahn', 'public' => 'Nahverkehr', 'ride' => 'Mitfahrt'])
<x-vdbs.forms.form-section title="Fahrten">
    <p class="vdbs-hint">Bei Fahrten mit dem PKW rechnet das Portal Kilometer mal {{ number_format($settings->int('km_rate_cents') / 100, 2, ',', '') }} € je km. Bitte geben Sie die kürzeste übliche Strecke an. Fahrkarten und Parkgebühren brauchen einen Beleg, die Kilometerpauschale nicht.</p>
    @for ($i = 0; $i < $rows; $i++)
        @php($item = $items[$i] ?? null)
        @php($extra = $item ? (array) $item->extra : [])
        <fieldset style="margin-bottom:var(--space-5);padding:var(--space-4);border:1px solid var(--color-border,#ccc)">
            <legend>Fahrt {{ $i + 1 }}@if ($item && $item->amount_cents > 0) · {{ number_format($item->amount_cents / 100, 2, ',', '.') }} €@endif</legend>
            @if ($item) <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}"> @endif
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input :id="'item-'.$i.'-date'" :name="'items['.$i.'][date]'" type="date" label="Datum" :optional="true" :value="old('items.'.$i.'.date', $item?->date?->format('Y-m-d'))" />
                <x-vdbs.forms.select :id="'item-'.$i.'-mode'" :name="'items['.$i.'][mode]'" label="Verkehrsmittel" :options="$modes" :value="old('items.'.$i.'.mode', $extra['mode'] ?? 'car')" />
                <x-vdbs.forms.input :id="'item-'.$i.'-from'" :name="'items['.$i.'][from]'" label="Start" :optional="true" maxlength="100" :value="old('items.'.$i.'.from', $extra['from'] ?? null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-to'" :name="'items['.$i.'][to]'" label="Ziel" :optional="true" maxlength="100" :value="old('items.'.$i.'.to', $extra['to'] ?? null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-km'" :name="'items['.$i.'][km]'" label="Kilometer (nur PKW)" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.km', isset($extra['km']) && $extra['km'] > 0 ? str_replace('.', ',', (string) $extra['km']) : null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-ticket'" :name="'items['.$i.'][ticket]'" label="Fahrpreis in Euro (Bahn, Nahverkehr, Mitfahrt)" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.ticket', ! empty($extra['ticket_cents']) ? \App\Modules\Seminars\Support\Money::toInput($extra['ticket_cents']) : null)" />
                <x-vdbs.forms.input :id="'item-'.$i.'-parking'" :name="'items['.$i.'][parking]'" label="Parkgebühr oder Maut in Euro" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.parking', ! empty($extra['parking_cents']) ? \App\Modules\Seminars\Support\Money::toInput($extra['parking_cents']) : null)" />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.checkbox :id="'item-'.$i.'-round'" :name="'items['.$i.'][round_trip]'" label="Hin- und Rückfahrt (Strecke zählt doppelt)" :checked="(bool) old('items.'.$i.'.round_trip', $extra['round_trip'] ?? false)" />
            @if ($item && $item->files->isNotEmpty())
                <p style="margin:var(--space-2) 0">Beleg: @foreach ($item->files as $file)<a href="{{ route($audience.'-forms.file', [$application, $file]) }}">{{ $file->original_name }}</a>@if (! $loop->last), @endif @endforeach</p>
            @endif
            <label for="file-{{ $i }}" style="display:block;font-weight:600">Beleg für Fahrkarte oder Parkgebühr</label>
            <input id="file-{{ $i }}" type="file" name="files[{{ $i }}]" accept="application/pdf,image/jpeg,image/png">
        </fieldset>
    @endfor
    <x-vdbs.actions.button type="submit" name="action" value="add_row" variant="secondary" icon="plus">Weitere Fahrt</x-vdbs.actions.button>
</x-vdbs.forms.form-section>

<x-vdbs.forms.form-section title="Auszahlung">
    <x-vdbs.forms.form-grid :columns="2">
        <x-vdbs.forms.input name="account_holder" label="Kontoinhaber:in" :optional="true" maxlength="150" :value="old('account_holder', $application->account_holder)" />
        <x-vdbs.forms.input name="iban" label="IBAN" :optional="true" maxlength="40" autocomplete="off" :value="old('iban', $iban)" hint="Wird verschlüsselt gespeichert und 30 Tage nach der Auszahlung gelöscht." />
    </x-vdbs.forms.form-grid>
</x-vdbs.forms.form-section>
