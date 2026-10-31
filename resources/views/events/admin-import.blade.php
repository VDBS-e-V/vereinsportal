@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen', 'href' => route('events.admin.index')], ['label' => $event->title, 'href' => route('events.admin.show', $event)], ['label' => 'Import']];
    $importErrors = session('import_errors', []);
@endphp

<x-layouts.app title="Anmeldungen importieren" width="medium">
    <x-vdbs.layout.page-header title="Anmeldungen importieren" lead="Melden Sie ganze Gruppen im Auftrag von Lehrkräften an." :breadcrumb="$crumbs" />

    @if ($importErrors !== [])
        <x-vdbs.feedback.alert variant="error" title="Die Datei wurde nicht importiert ({{ session('import_error_count') }} Fehler)">
            Bitte korrigieren Sie die Datei und laden Sie sie erneut hoch. Es wurde nichts angelegt.
            <ul>@foreach ($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-vdbs.feedback.alert>
    @endif

    <x-vdbs.content.prose>
        <ol>
            <li>Laden Sie die <a href="{{ route('events.admin.import.template', $event) }}">Vorlage</a> herunter und tragen Sie die Anmeldungen ein.</li>
            <li>Speichern Sie als <strong>CSV</strong> (Semikolon oder Komma).</li>
            <li>Wählen Sie die Datei aus und starten Sie den Import.</li>
        </ol>
        <ul>
            <li>Spalten: <strong>Name, Schule, Klasse, Geburtsdatum</strong> (TT.MM.JJJJ) und <strong>E-Mail</strong>. Alle sind Pflicht.</li>
            <li>Enthält die Datei fehlerhafte Zeilen oder passen nicht alle Anmeldungen in die freien Plätze, wird <strong>nichts</strong> importiert.</li>
            <li>Importierte Anmeldungen gelten sofort als bestätigt und erhalten einen Ticketcode.</li>
            <li>Höchstens {{ \App\Modules\Events\Services\EventRegistrations::MAX_ROWS }} Zeilen und 2 MB.</li>
        </ul>
    </x-vdbs.content.prose>

    <x-vdbs.forms.form :action="route('events.admin.import.store', $event)" :files="true" style="margin-top:var(--space-6)">
        <x-vdbs.forms.file name="file" label="CSV-Datei" accept=".csv,.txt,text/csv" :required="true" />
        <x-vdbs.forms.checkbox name="send_mails" label="Anmeldebestätigungen mit Ticketcode per E-Mail an die angegebenen Adressen senden" :checked="true" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="upload">Importieren</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('events.admin.show', $event)">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
