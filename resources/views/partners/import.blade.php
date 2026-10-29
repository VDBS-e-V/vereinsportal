@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Schulen', 'href' => route('partners.index')], ['label' => 'Import']];
    $importErrors = session('import_errors', []);
@endphp

<x-layouts.app title="Schulen importieren" width="medium">
    <x-vdbs.layout.page-header title="Schulen importieren" lead="Laden Sie eine CSV-Datei mit Schulen hoch, zum Beispiel aus einer Tabelle." :breadcrumb="$crumbs" />

    @if ($importErrors !== [])
        <x-vdbs.feedback.alert variant="error" title="Die Datei wurde nicht importiert ({{ session('import_error_count') }} Fehler)">
            Bitte korrigieren Sie die Datei und laden Sie sie erneut hoch. Es wurde nichts angelegt.
            <ul>
                @foreach ($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @if (session('import_error_count') > count($importErrors))
                <p>Es werden die ersten {{ count($importErrors) }} Fehler angezeigt.</p>
            @endif
        </x-vdbs.feedback.alert>
    @endif

    <x-vdbs.content.prose>
        <h2>So geht es</h2>
        <ol>
            <li>Laden Sie die <a href="{{ route('partners.import.template') }}">Vorlage</a> herunter und tragen Sie Ihre Schulen ein (Excel oder ein Texteditor).</li>
            <li>Speichern Sie als <strong>CSV</strong> (Trennzeichen Semikolon oder Komma).</li>
            <li>Wählen Sie die Datei aus und starten Sie den Import.</li>
        </ol>
        <ul>
            <li>Pflicht ist nur die Spalte <strong>Name</strong>. Schulart und Bundesland werden mit ihren Bezeichnungen erkannt (z. B. „Gymnasium“, „Sachsen“ oder „SN“).</li>
            <li>Enthält die Datei fehlerhafte Zeilen, wird <strong>nichts</strong> importiert, und jede fehlerhafte Zeile wird genannt.</li>
            <li>Schulen, die es mit gleichem Namen am selben Ort schon gibt, werden übersprungen und nicht überschrieben.</li>
            <li>Höchstens {{ \App\Modules\Partners\Services\PartnerImporter::MAX_ROWS }} Zeilen und 2 MB pro Datei.</li>
        </ul>
    </x-vdbs.content.prose>

    <x-vdbs.forms.form :action="route('partners.import.store')" :files="true" style="margin-top:var(--space-6)">
        <x-vdbs.forms.file name="file" label="CSV-Datei" accept=".csv,.txt,text/csv" :required="true" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="upload">Importieren</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('partners.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
