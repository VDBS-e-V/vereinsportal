@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Seminare', 'href' => route('school.seminars.index')], ['label' => $seminar->number]];
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="$seminar->number" :breadcrumb="$crumbs" />

    <p><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></p>

    <x-vdbs.data.detail-list :columns="2">
        <x-vdbs.data.detail-item label="Format">{{ $seminar->format }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Teilnehmende">{{ $seminar->participants }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gewünschter Zeitraum">{{ $seminar->desired_period }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ansprechperson">{{ $seminar->contact_name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="E-Mail-Adresse">{{ $seminar->contact_email }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anmerkungen">{{ $seminar->request_note }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @include('seminars._quote-school', ['seminar' => $seminar])

    @php
        $contractUploadable = in_array($seminar->status, [\App\Modules\Seminars\Enums\SeminarStatus::QuoteSent, \App\Modules\Seminars\Enums\SeminarStatus::Released], true);
        $contracts = $seminar->documents()->where('kind', \App\Modules\Seminars\Models\SeminarDocument::SCHOOL_CONTRACT)->get();
    @endphp

    @if ($contractUploadable || $contracts->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Vertrag</h2>

        @error('contract')
            <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
        @enderror

        @foreach ($contracts as $document)
            <p><a href="{{ route('seminars.documents.download', $document) }}">{{ $document->original_name }}</a>@if ($seminar->school_contract_signed_on), unterschrieben am {{ $seminar->school_contract_signed_on->format('d.m.Y') }}@endif</p>
        @endforeach

        @if ($contractUploadable)
            <x-vdbs.forms.form :action="route('school.seminars.contract', $seminar)" :files="true" :summary="false">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.file name="file" label="Ihr unterschriebener Vertrag" :required="true" />
                    <x-vdbs.forms.input name="signed_on" label="Datum der Unterschrift" type="date" :required="true" :value="old('signed_on')" />
                </x-vdbs.forms.form-grid>
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Vertrag hochladen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @endif

    @if ($seminar->report_sent_at)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Bericht und Ergebnisse</h2>
        @foreach ($seminar->documents()->where('kind', 'report')->get() as $document)
            <p>Bericht: <a href="{{ route('seminars.documents.download', $document) }}">{{ $document->original_name }}</a></p>
        @endforeach
        @if (filled($seminar->results_url))
            <p>Ergebnisse: <a href="{{ $seminar->results_url }}" rel="noopener noreferrer">Ordner öffnen</a>@if ($seminar->results_expires_on) (erreichbar bis {{ $seminar->results_expires_on->format('d.m.Y') }})@endif</p>
        @endif
    @endif

    @if ($seminar->report_sent_at)
        @php $feedback = \App\Modules\Seminars\Models\SeminarFeedback::query()->where('seminar_id', $seminar->id)->first(); @endphp
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Ihre Rückmeldung</h2>

        @error('feedback')
            <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
        @enderror

        @if ($feedback)
            <p>Danke, Ihre Rückmeldung ist am {{ $feedback->created_at?->format('d.m.Y') }} eingegangen.</p>
        @else
            <x-vdbs.forms.form :action="route('school.seminars.feedback', $seminar)">
                @foreach (\App\Modules\Seminars\Models\SeminarFeedback::QUESTIONS as $key => $question)
                    <x-vdbs.forms.radio-group layout="inline" :name="'ratings['.$key.']'" :legend="$question.' (5 = sehr gut, 1 = schlecht)'" :required="true" :options="['5' => '5', '4' => '4', '3' => '3', '2' => '2', '1' => '1']" :value="old('ratings.'.$key)" />
                @endforeach
                <x-vdbs.forms.textarea name="comment" label="Was war gut, was sollen wir ändern?" :optional="true" maxlength="3000" :value="old('comment')" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Rückmeldung senden</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @endif

    @if ($seminar->sessions->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Termine</h2>
        <ul>
            @foreach ($seminar->sessions as $session)
                <li>{{ $session->starts_at->format('d.m.Y, H:i') }} Uhr{{ filled($session->location) ? ', '.$session->location : '' }}</li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
