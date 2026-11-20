@php
    use App\Modules\Seminars\Models\SeminarDocument;
    use App\Modules\Seminars\Services\PrintPlans;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $seminar->number], ['label' => 'Druckplaner']];
@endphp

<x-layouts.app title="Druckplaner" width="wide">
    <x-vdbs.layout.page-header :title="'Druckplaner: '.$seminar->title" lead="Legen Sie je Dokument fest, welche Seiten wie oft gedruckt werden. Am Ende erhalten Sie eine PDF für einen einzigen Druckauftrag, in der von allem genug vorhanden ist." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminars.files.folder', $seminar)" icon="download">Seminarmappe als ZIP</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @error('plan')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @if ($documents->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine druckbaren Dokumente" icon="printer"><p>Der Druckplaner nutzt PDF-Dokumente des Seminars, zum Beispiel Material. Verträge sind ausgenommen.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.forms.form :action="route('seminars.files.save', $seminar)" method="put" :summary="false">
            @foreach ($documents as $document)
                @php $item = $items->get($document->id); @endphp
                <x-vdbs.forms.form-section :title="$document->original_name.' ('.$document->pages.' Seiten, '.$document->kindLabel().')'">
                    <x-vdbs.forms.checkbox :id="'use-'.$document->id" :name="'rows['.$document->id.'][use]'" label="Dieses Dokument drucken" value="1" :checked="(bool) old('rows.'.$document->id.'.use', $item !== null)" />
                    <x-vdbs.forms.form-grid :columns="4">
                        <x-vdbs.forms.input :id="'pages-'.$document->id" :name="'rows['.$document->id.'][pages]'" label="Seiten" :optional="true" :value="old('rows.'.$document->id.'.pages', $item['pages'] ?? 'alle')" hint="„alle“ oder zum Beispiel 1-3, 5" />
                        <x-vdbs.forms.input :id="'copies-'.$document->id" :name="'rows['.$document->id.'][copies]'" label="Exemplare" type="number" :value="old('rows.'.$document->id.'.copies', $item['copies'] ?? 1)" />
                        <x-vdbs.forms.select :id="'color-'.$document->id" :name="'rows['.$document->id.'][color]'" label="Farbe" :options="['bw' => 'Schwarzweiß', 'color' => 'Farbe']" :value="old('rows.'.$document->id.'.color', ($item['color'] ?? false) ? 'color' : 'bw')" />
                        <x-vdbs.forms.select :id="'per-'.$document->id" :name="'rows['.$document->id.'][per_sheet]'" label="Seiten pro Blatt" :options="collect($perSheet)->mapWithKeys(fn ($n): array => [$n => (string) $n])->all()" :value="old('rows.'.$document->id.'.per_sheet', $item['per_sheet'] ?? 1)" />
                    </x-vdbs.forms.form-grid>
                    <x-vdbs.forms.checkbox :id="'duplex-'.$document->id" :name="'rows['.$document->id.'][duplex]'" label="Doppelseitig drucken" value="1" :checked="(bool) old('rows.'.$document->id.'.duplex', $item['duplex'] ?? false)" />
                    @if ($item !== null)
                        @php $c = PrintPlans::counts($item, (int) $document->pages); @endphp
                        <p class="vdbs-hint">{{ $c['pages'] }} Seiten ergeben {{ $c['sheets'] }} Blatt-Seiten je Exemplar, zusammen {{ $c['total_paper'] }} Blatt Papier.</p>
                    @endif
                </x-vdbs.forms.form-section>
            @endforeach
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Druckplan speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        @if ($totals)
            <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Zusammenfassung</h2>
            <x-vdbs.data.detail-list :columns="3" layout="inline">
                <x-vdbs.data.detail-item label="Schwarzweiß (Blatt Papier)">{{ $totals['bw'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Farbe (Blatt Papier)">{{ $totals['color'] }}</x-vdbs.data.detail-item>
                <x-vdbs.data.detail-item label="Papier insgesamt">{{ $totals['total'] }}</x-vdbs.data.detail-item>
            </x-vdbs.data.detail-list>
            <p style="margin-top:var(--space-4)"><x-vdbs.actions.button :href="route('seminars.files.pdf', $seminar)" icon="download">Druck-PDF erzeugen</x-vdbs.actions.button></p>
        @endif
    @endif

    @if ($unusable->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Nicht im Druckplaner</h2>
        <p>Diese Dateien sind keine PDF oder ihre Seitenzahl ließ sich nicht lesen (zum Beispiel PDF 1.5 mit komprimierter Struktur). Speichern Sie sie als PDF 1.4 und laden Sie sie neu hoch.</p>
        <ul>@foreach ($unusable as $document)<li>{{ $document->original_name }} ({{ $document->kindLabel() }})</li>@endforeach</ul>
    @endif
</x-layouts.app>
