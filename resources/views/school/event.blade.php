@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Schulen', 'href' => route('school.home')], ['label' => 'Veranstaltungen', 'href' => route('school.events.index')], ['label' => $event->title]];
    $importErrors = session('import_errors', []);
@endphp

<x-layouts.app :title="$event->title" width="wide">
    <x-vdbs.layout.page-header :title="$event->title" :lead="$event->sessions->map(fn ($s) => $s->period())->implode(' · ')" :breadcrumb="$crumbs">
        <x-slot:meta><x-vdbs.data.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-vdbs.data.badge></x-slot:meta>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    @if ($importErrors !== [])
        <x-vdbs.feedback.alert variant="error" title="Die Datei wurde nicht übernommen ({{ session('import_error_count') }} Fehler)">
            Bitte korrigieren Sie die Datei und laden Sie sie erneut hoch. Es wurde niemand angemeldet.
            <ul>@foreach ($importErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-vdbs.feedback.alert>
    @endif

    <section aria-labelledby="upload-heading">
        <h2 id="upload-heading" style="font-size:var(--font-size-h3)">Liste hochladen</h2>

        @if ($open)
            <x-vdbs.content.prose>
                <ol>
                    <li>Laden Sie die <a href="{{ route('school.events.template', $event) }}">Vorlage</a> herunter und tragen Sie Ihre Schülerinnen und Schüler ein.</li>
                    <li>Speichern Sie als <strong>CSV</strong> (Semikolon oder Komma) und laden Sie die Datei hier hoch.</li>
                </ol>
                <ul>
                    <li>Spalten: <strong>Name, Klasse, Geburtsdatum</strong> (TT.MM.JJJJ) und <strong>E-Mail</strong>. Die Schule tragen wir selbst ein.</li>
                    <li>Bei einem Fehler in der Datei wird <strong>niemand</strong> angemeldet, und jede fehlerhafte Zeile wird genannt.</li>
                    <li>Mit Ihrer Liste bestätigen Sie, dass die Schülerinnen und Schüler (bzw. ihre Erziehungsberechtigten) der Anmeldung zugestimmt haben. Für die Erlaubnis zur Teilnahme sind die Schülerinnen und Schüler selbst verantwortlich.</li>
                    <li>Höchstens {{ $maxRows }} Zeilen und 2 MB.</li>
                </ul>
            </x-vdbs.content.prose>

            <x-vdbs.forms.form :action="route('school.events.import', $event)" :files="true" style="margin-top:var(--space-5)">
                <x-vdbs.forms.file name="file" label="CSV-Datei" accept=".csv,.txt,text/csv" :required="true" />
                <x-vdbs.forms.checkbox name="send_mails" label="Anmeldebestätigungen mit Ticketcode per E-Mail an die angegebenen Adressen senden" :checked="true" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="upload">Liste anmelden</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @else
            <x-vdbs.feedback.alert variant="info">Für diese Veranstaltung nimmt das Portal keine Listen mehr an.</x-vdbs.feedback.alert>
        @endif
    </section>

    <section aria-labelledby="own-heading" style="margin-top:var(--space-8)">
        <h2 id="own-heading" style="font-size:var(--font-size-h3)">Angemeldet von Ihrer Schule ({{ $registrations->count() }})</h2>

        @if ($registrations->isEmpty())
            <p>Noch niemand angemeldet.</p>
        @else
            <x-vdbs.data.table caption="Angemeldete Schülerinnen und Schüler" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'class', 'label' => 'Klasse'], ['key' => 'ticket', 'label' => 'Ticket'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'action', 'label' => 'Aktion']]">
                @foreach ($registrations as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->class_name }}</td>
                        <td><code>{{ $row->waitlisted ? '–' : $row->ticket() }}</code></td>
                        <td>@if ($row->waitlisted)<x-vdbs.data.badge tone="warning">Warteliste</x-vdbs.data.badge>@elseif ($row->isConfirmed())<x-vdbs.data.badge tone="success">Angemeldet</x-vdbs.data.badge>@else<x-vdbs.data.badge>Offen</x-vdbs.data.badge>@endif</td>
                        <td><form method="post" action="{{ route('school.events.registrations.destroy', [$event, $row]) }}">@csrf @method('delete')<x-vdbs.actions.button type="submit" size="s" variant="danger">Abmelden</x-vdbs.actions.button></form></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
