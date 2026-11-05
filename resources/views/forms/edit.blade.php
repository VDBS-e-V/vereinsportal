@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Anträge', 'href' => route('forms.index')], ['label' => $application->number]];
    $items = $application->items->values();
    $deadlineNote = 'Belege aus einem Jahr können bis Ende Februar des Folgejahres eingereicht werden.';
@endphp
<x-layouts.app :title="$application->typeLabel().' '.$application->number" width="wide">
    <x-vdbs.layout.page-header :title="$application->typeLabel()" :lead="'Antrag '.$application->number.' · Stand: '.$application->statusLabel()" :breadcrumb="$crumbs" />

    @if ($application->status === 'returned' && $application->rejection_note)
        <x-vdbs.feedback.alert variant="warning" title="Rückfrage der Geschäftsstelle"><p>{{ $application->rejection_note }}</p></x-vdbs.feedback.alert>
    @endif
    @error('form') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror
    @if ($problems !== [])
        <x-vdbs.feedback.alert variant="info" title="Das fehlt noch zum Einreichen"><ul>@foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul></x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="route('forms.update', $application)" method="put" :files="true" :summary="false">
        <x-vdbs.forms.form-section title="1 Anlass">
            <x-vdbs.forms.input name="purpose" label="Zweck der Ausgaben" :required="true" maxlength="300" :value="old('purpose', $application->purpose)" hint="z. B. Material für das Seminar an der Schule" />
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="seminar_id" label="Seminar (freiwillig)" :optional="true" placeholder="Kein Seminar" :options="$seminars" :value="old('seminar_id', $application->seminar_id)" />
                <x-vdbs.forms.select name="cost_center" label="Kostenstelle (freiwillig)" :optional="true" placeholder="Keine" :options="$costCenters" :value="old('cost_center', $application->cost_center)" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="2 Positionen und Belege">
            <p class="vdbs-hint">{{ $deadlineNote }} Zu jeder Position gehört ein Beleg (PDF, JPEG oder PNG, je höchstens 15 MB).</p>
            @for ($i = 0; $i < $rows; $i++)
                @php($item = $items[$i] ?? null)
                <fieldset style="margin-bottom:var(--space-5);padding:var(--space-4);border:1px solid var(--color-border,#ccc)">
                    <legend>Position {{ $i + 1 }}</legend>
                    @if ($item) <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}"> @endif
                    <x-vdbs.forms.form-grid :columns="2">
                        <x-vdbs.forms.input :id="'item-'.$i.'-date'" :name="'items['.$i.'][date]'" type="date" label="Datum des Belegs" :optional="true" :value="old('items.'.$i.'.date', $item?->date?->format('Y-m-d'))" />
                        <x-vdbs.forms.select :id="'item-'.$i.'-kind'" :name="'items['.$i.'][kind]'" label="Art" :options="\App\Modules\Forms\Models\FormItem::KINDS" :value="old('items.'.$i.'.kind', $item?->kind ?? 'material')" />
                        <x-vdbs.forms.input :id="'item-'.$i.'-description'" :name="'items['.$i.'][description]'" label="Beschreibung" :optional="true" maxlength="200" :value="old('items.'.$i.'.description', $item?->description)" />
                        <x-vdbs.forms.input :id="'item-'.$i.'-amount'" :name="'items['.$i.'][amount]'" label="Betrag in Euro" :optional="true" inputmode="decimal" :value="old('items.'.$i.'.amount', $item ? \App\Modules\Seminars\Support\Money::toInput($item->amount_cents) : null)" hint="z. B. 12,50" />
                    </x-vdbs.forms.form-grid>
                    @if ($item && $item->files->isNotEmpty())
                        <p style="margin:var(--space-2) 0">Beleg: @foreach ($item->files as $file)<a href="{{ route('forms.file', [$application, $file]) }}">{{ $file->original_name }}</a>@if (! $loop->last), @endif @endforeach</p>
                    @endif
                    <label for="file-{{ $i }}" style="display:block;font-weight:600">{{ $item && $item->files->isNotEmpty() ? 'Weiteren Beleg hinzufügen' : 'Beleg hochladen' }}</label>
                    <input id="file-{{ $i }}" type="file" name="files[{{ $i }}]" accept="application/pdf,image/jpeg,image/png">
                </fieldset>
            @endfor
            <x-vdbs.actions.button type="submit" name="action" value="add_row" variant="secondary" icon="plus">Weitere Position</x-vdbs.actions.button>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="3 Auszahlung">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="account_holder" label="Kontoinhaber:in" :optional="true" maxlength="150" :value="old('account_holder', $application->account_holder)" />
                <x-vdbs.forms.input name="iban" label="IBAN" :optional="true" maxlength="40" autocomplete="off" :value="old('iban', $iban)" hint="Wird verschlüsselt gespeichert und 30 Tage nach der Auszahlung gelöscht." />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="4 Erklärung und Absenden">
            <x-vdbs.forms.checkbox name="declaration" label="Die Angaben sind richtig, die Belege sind Originalausgaben und ich habe keine Doppelerstattung beantragt." :checked="(bool) old('declaration', ($application->data['declaration'] ?? false))" />
            <p><strong>Summe: {{ $application->euro() }}</strong> (wird beim Speichern berechnet)</p>
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" name="action" value="submit" icon="send">Antrag einreichen</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" name="action" value="save" variant="secondary" icon="save">Entwurf speichern</x-vdbs.actions.button>
            @if ($application->status === 'draft')
                <x-vdbs.actions.button type="submit" name="action" value="discard" variant="text" icon="trash-2">Entwurf löschen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
