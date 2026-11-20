@php
    use App\Modules\Seminars\Enums\SeminarStatus;
    use App\Modules\Seminars\Models\SeminarDocument;

    $contracts = $seminar->documents->where('kind', SeminarDocument::SCHOOL_CONTRACT);
    $uploadable = in_array($seminar->status, [SeminarStatus::QuoteSent, SeminarStatus::Released], true);
    $releasable = $seminar->status === SeminarStatus::QuoteSent;
@endphp

@if ($uploadable || $contracts->isNotEmpty() || $seminar->release_basis)
    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Schulvertrag und Freigabe</h2>

    @error('contract')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror
    @error('release')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @if ($seminar->release_basis)
        <x-vdbs.feedback.alert variant="success">
            Freigegeben am {{ $seminar->released_at?->format('d.m.Y') }}@if ($seminar->release_basis === 'contract') mit unterschriebenem Schulvertrag @else auf verbindliche Zusage der Schule: {{ $seminar->release_reason }} @endif
        </x-vdbs.feedback.alert>
    @endif

    @if ($contracts->isNotEmpty())
        <ul>
            @foreach ($contracts as $document)
                <li><a href="{{ route('seminars.documents.download', $document) }}">{{ $document->original_name }}</a>, hochgeladen am {{ $document->created_at?->format('d.m.Y') }}@if ($seminar->school_contract_signed_on), unterschrieben am {{ $seminar->school_contract_signed_on->format('d.m.Y') }}@endif</li>
            @endforeach
        </ul>
    @endif

    @if ($uploadable)
        <x-vdbs.forms.form :action="route('seminars.office.contract', $seminar)" :files="true" :summary="false" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.file name="file" label="Unterschriebener Schulvertrag (von der Schule)" :required="true" />
                <x-vdbs.forms.input name="signed_on" label="Datum der Unterschrift" type="date" :required="true" :value="old('signed_on')" />
            </x-vdbs.forms.form-grid>
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Vertrag speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @if ($releasable)
        <x-vdbs.forms.form :action="route('seminars.office.release', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
            <input type="hidden" name="basis" value="contract">
            <p>Mit Vertrag: Der unterschriebene Schulvertrag liegt vor.</p>
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check" :disabled="$contracts->isEmpty()">Mit Vertrag freigeben</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        <x-vdbs.forms.form :action="route('seminars.office.release', $seminar)" :summary="false">
            <input type="hidden" name="basis" value="commitment">
            <x-vdbs.forms.textarea id="release-reason" name="reason" label="Freigabe auf verbindliche Zusage der Schule: Begründung" :required="true" maxlength="2000" hint="Zum Beispiel: Zusage per E-Mail vom 12.03., der Vertrag folgt." :value="old('reason')" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary">Auf Zusage freigeben</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif
@endif
