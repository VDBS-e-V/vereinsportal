@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Teamkontakte', 'href' => route('contacts.index')], ['label' => 'Import']];
    $importErrors = session('import_errors', []);
    $statusLabels = ['new' => 'Neu', 'existing' => 'Vorhanden', 'duplicate' => 'Doppelt', 'error' => 'Fehler'];
    $statusTones = ['new' => 'success', 'existing' => 'info', 'duplicate' => 'neutral', 'error' => 'error'];
@endphp

<x-layouts.app title="Mailingliste importieren" width="medium">
    <x-vdbs.layout.page-header title="Mailingliste importieren" lead="Es genügen die E-Mail-Adressen. Zuerst sehen Sie eine Vorschau, erst danach wird gespeichert." :breadcrumb="$crumbs" />

    @if ($importErrors !== [])
        <x-vdbs.feedback.alert variant="error" title="Die Liste wurde nicht importiert ({{ session('import_error_count') }} Fehler)">
            Bitte korrigieren Sie die Liste und prüfen Sie sie erneut. Es wurde nichts gespeichert.
            <ul>@foreach ($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-vdbs.feedback.alert>
    @endif

    @if ($preview === null)
        <x-vdbs.content.prose>
            <h2>So geht es</h2>
            <ol>
                <li>Laden Sie die <a href="{{ route('contacts.import.template') }}">Vorlage</a> herunter und tragen Sie die Adressen ein, oder fügen Sie sie unten direkt ein (eine Adresse pro Zeile, auch aus Excel).</li>
                <li>Prüfen Sie die Vorschau. Nichts wird gespeichert, bis Sie bestätigen.</li>
                <li>Danach senden Sie die persönlichen Zugänge aus der Liste der Teamkontakte.</li>
            </ol>
            <p>Erlaubte Spalten: <strong>E-Mail</strong> (Pflicht), Vorname, Nachname, Telefon. Höchstens {{ \App\Modules\Contacts\Services\ContactImporter::MAX_ROWS }} Adressen, Datei bis 2 MB. Bitte entfernen Sie Abgemeldete vorher aus dem Export des Mailsystems.</p>
        </x-vdbs.content.prose>

        <x-vdbs.forms.form :action="route('contacts.import.preview')" :files="true" style="margin-top:var(--space-6)">
            <x-vdbs.forms.file name="file" label="CSV- oder Textdatei" accept=".csv,.txt,text/csv,text/plain" :optional="true" />
            <x-vdbs.forms.textarea name="list" label="Oder Adressen einfügen" :optional="true" :rows="8" hint="Eine Adresse pro Zeile, oder Zeilen mit E-Mail;Vorname;Nachname;Telefon" />
            <x-vdbs.forms.checkbox name="consent" label="Die Einwilligung zur E-Mail-Kommunikation liegt für alle Adressen dieser Liste vor." :required="true" />
            <x-slot:actions>
                <x-vdbs.actions.button type="submit" icon="eye">Vorschau anzeigen</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="text" :href="route('contacts.index')">Abbrechen</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    @else
        @php($counts = $preview['counts'])
        <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
            <x-vdbs.data.stat label="Adressen in der Liste" value="{{ $counts['total'] }}" icon="users" />
            <x-vdbs.data.stat label="Neu" value="{{ $counts['new'] }}" icon="plus" tone="success" />
            <x-vdbs.data.stat label="Fehler" value="{{ $counts['errors'] }}" icon="triangle-alert" :tone="$counts['errors'] > 0 ? 'error' : 'neutral'" />
        </x-vdbs.layout.grid>

        <p>Vorhanden: {{ $counts['existing'] }} (leere Felder werden ergänzt) · Doppelt in der Liste: {{ $counts['duplicates'] }} (übersprungen) · Gesperrt oder abgemeldet: {{ $counts['blocked'] }} (werden übernommen, aber nicht angeschrieben)</p>

        @if ($preview['errors'] !== [])
            <x-vdbs.feedback.alert variant="error" title="Die Liste hat Fehler">
                Es wird nichts importiert, solange Fehler in der Liste stehen. Bitte korrigieren Sie sie und prüfen Sie erneut.
                <ul>@foreach (array_slice($preview['errors'], 0, 50) as $error)<li>{{ $error }}</li>@endforeach</ul>
            </x-vdbs.feedback.alert>
            <p style="margin-top:var(--space-4)"><x-vdbs.actions.button :href="route('contacts.import')" icon="arrow-left">Zurück</x-vdbs.actions.button></p>
        @else
            <x-vdbs.data.table caption="Vorschau der ersten Zeilen" :columns="[['key' => 'line', 'label' => 'Zeile'], ['key' => 'email', 'label' => 'E-Mail-Adresse'], ['key' => 'status', 'label' => 'Ergebnis']]">
                @foreach (array_slice($preview['rows'], 0, 100) as $row)
                    <tr>
                        <td>{{ $row['line'] }}</td>
                        <td>{{ $row['email'] }}</td>
                        <td><x-vdbs.data.badge :tone="$statusTones[$row['status']]">{{ $statusLabels[$row['status']] }}</x-vdbs.data.badge> {{ $row['message'] }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            @if (count($preview['rows']) > 100)
                <p>Gezeigt werden die ersten 100 von {{ count($preview['rows']) }} Zeilen.</p>
            @endif

            <x-vdbs.forms.form :action="route('contacts.import.store')" style="margin-top:var(--space-6)">
                <input type="hidden" name="list" value="{{ $list }}">
                <x-vdbs.forms.checkbox name="consent" label="Die Einwilligung zur E-Mail-Kommunikation liegt für alle Adressen dieser Liste vor." :required="true" />
                <x-slot:actions>
                    <x-vdbs.actions.button type="submit" icon="check">{{ $counts['new'] }} Adressen importieren</x-vdbs.actions.button>
                    <x-vdbs.actions.button variant="text" :href="route('contacts.import')">Abbrechen</x-vdbs.actions.button>
                </x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @endif
</x-layouts.app>
