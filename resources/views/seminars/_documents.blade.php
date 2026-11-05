@php
    $documents = $seminar->documents()->with('uploadedBy.person')->get();
    $closure = $seminar->currentClosure();
    $closed = $seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Completed;
    $canReopen = auth()->user()?->can('seminars.reopen');
    $totalMb = round($documents->sum('size') / 1048576, 1);
    $kinds = \App\Modules\Seminars\Models\SeminarDocument::KINDS;
@endphp

<section aria-labelledby="documents-heading" style="margin-top:var(--space-8)">
    <h2 id="documents-heading" style="font-size:var(--font-size-h3)">Dokumente</h2>

    <x-vdbs.feedback.alert variant="info">Keine Fotos von Teilnehmenden: Bilddateien werden nicht angenommen. Namenslisten von Schülerinnen und Schülern nur als Dokument ablegen, wenn die Schule sie liefert; sie werden nach der Aufbewahrungsfrist gelöscht.</x-vdbs.feedback.alert>

    @if ($closed && $closure)
        <p>Abgeschlossen am {{ $closure->closed_at->format('d.m.Y') }}. Verträge, Kostenvoranschläge und Rechnungen sind geschützt.
            @if ($canReopen)
                <form method="post" action="{{ route('seminars.closure.reopen', $seminar) }}" style="margin-top:var(--space-2)">@csrf
                    <x-vdbs.forms.input name="reason" label="Begründung zum Wiederöffnen" :required="true" maxlength="500" />
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="refresh-cw">Abschluss wieder öffnen</x-vdbs.actions.button>
                </form>
            @endif
        </p>
    @endif

    @if ($documents->isEmpty())
        <p>Noch keine Dokumente abgelegt.</p>
    @else
        <x-vdbs.data.table caption="Dokumente des Seminars" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'documents-form'], ['key' => 'kind', 'label' => 'Art'], ['key' => 'title', 'label' => 'Titel'], ['key' => 'date', 'label' => 'Abgelegt'], ['key' => 'by', 'label' => 'Von'], ['key' => 'size', 'label' => 'Größe'], ['key' => 'until', 'label' => 'Aufbewahrt bis']]">
            @foreach ($documents as $document)
                <tr>
                    <td class="vdbs-table__select"><span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $document->id }}" form="documents-form" aria-label="{{ $document->title }} auswählen"></span></td>
                    <td>{{ $document->kindLabel() }}@if ($document->isProtected() && $closed) <x-vdbs.data.badge tone="info">geschützt</x-vdbs.data.badge>@endif</td>
                    <td><a href="{{ route('seminars.documents.download', [$seminar, $document]) }}">{{ $document->title }}</a><br><span class="vdbs-hint">{{ $document->original_name }}</span></td>
                    <td>{{ $document->created_at->format('d.m.Y') }}</td>
                    <td>{{ $document->uploadedBy?->person?->fullName() ?: '–' }}</td>
                    <td>{{ $document->size >= 1048576 ? round($document->size / 1048576, 1).' MB' : max(1, round($document->size / 1024)).' KB' }}</td>
                    <td>{{ $document->retention_until?->format('d.m.Y') ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
        <form id="documents-form" method="post" action="{{ route('seminars.documents.destroy', $seminar) }}" style="margin-top:var(--space-3)">@csrf
            <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="documents-form">Nichts ausgewählt.</p>
            @if ($closed && $canReopen)
                <x-vdbs.forms.input name="reason" id="delete-reason" label="Begründung (für geschützte Dokumente nach dem Abschluss)" :optional="true" maxlength="300" />
            @endif
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2">Auswahl löschen</x-vdbs.actions.button>
        </form>
        <p class="vdbs-hint">{{ $documents->count() }} Dokumente, zusammen {{ $totalMb }} MB.</p>
    @endif

    <x-vdbs.forms.form :action="route('seminars.documents.store', $seminar)" :files="true" style="margin-top:var(--space-5)" :summary="false">
        <x-vdbs.forms.form-section title="Dokumente ablegen">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.select name="kind" label="Art" :required="true" :options="$kinds" placeholder="Bitte wählen" />
                <x-vdbs.forms.input name="title" label="Titel" :optional="true" maxlength="200" hint="Ohne Titel gilt der Dateiname. Bei mehreren Dateien gilt der Titel für alle." />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.file name="files[]" label="Dateien" :required="true" :multiple="true" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.odt,.ods,.zip" hint="PDF, Word, Excel, CSV, OpenDocument, Text oder ZIP. Mehrere Dateien sind möglich." />
        </x-vdbs.forms.form-section>
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="upload">Ablegen</x-vdbs.actions.button>
            @if ($documents->isNotEmpty() || $closure)
                <x-vdbs.actions.button variant="secondary" :href="route('seminars.folder', $seminar)" icon="download">Seminarmappe herunterladen (ZIP)</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.forms.form>
</section>
