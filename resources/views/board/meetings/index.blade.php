@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Sitzungen']];
    $columns = [['key' => 'sel', 'label' => 'Auswahl'], ['key' => 'date', 'label' => 'Termin'], ['key' => 'title', 'label' => 'Sitzung'], ['key' => 'location', 'label' => 'Ort'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'minutes', 'label' => 'Protokoll']];
    $tone = ['planned' => 'neutral', 'held' => 'success', 'cancelled' => 'neutral'];
@endphp

<x-layouts.app title="Vorstandssitzungen" width="wide">
    <x-vdbs.layout.page-header title="Vorstandssitzungen" lead="Sitzungen planen, Tagesordnung und Anwesenheit festhalten und das Protokoll schreiben. Alle Termine erscheinen im Kalender-Abo des Vorstands." :breadcrumb="$crumbs">
        <x-slot:actions><x-vdbs.actions.button :href="route('board.meetings.create')" icon="plus">Neue Sitzung</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.layout.page-header>

    <form method="post" action="{{ route('board.meetings.destroy-many') }}">
        @csrf
        @foreach ([['Anstehende Sitzungen', $upcoming, 'Keine Sitzung geplant'], ['Vergangene Sitzungen', $past, 'Noch keine vergangene Sitzung']] as [$heading, $meetings, $empty])
            <section style="margin-top:var(--space-6)">
                <h2 style="font-size:var(--font-size-h3)">{{ $heading }}</h2>
                @if ($meetings->isEmpty())
                    <p>{{ $empty }}.</p>
                @else
                    <x-vdbs.data.table :caption="$heading" :columns="$columns">
                        @foreach ($meetings as $meeting)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $meeting->id }}" aria-label="{{ $meeting->title }} am {{ $meeting->starts_at->format('d.m.Y') }} auswählen"></td>
                                <td>{{ $meeting->period() }}</td>
                                <td><a href="{{ route('board.meetings.show', $meeting) }}">{{ $meeting->title }}</a></td>
                                <td>{{ $meeting->location ?: '–' }}</td>
                                <td><x-vdbs.data.badge :tone="$tone[$meeting->status] ?? 'neutral'">{{ $meeting->statusLabel() }}</x-vdbs.data.badge></td>
                                <td>@if ($meeting->minutes_approved_on)<x-vdbs.data.badge tone="success">genehmigt</x-vdbs.data.badge>@elseif ($meeting->hasMinutes())<x-vdbs.data.badge tone="warning">Entwurf</x-vdbs.data.badge>@else – @endif</td>
                            </tr>
                        @endforeach
                    </x-vdbs.data.table>
                @endif
            </section>
        @endforeach
        <p style="margin-top:var(--space-3)"><x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Sitzungen samt Protokoll löschen?')">Ausgewählte löschen</x-vdbs.actions.button></p>
    </form>

    <x-vdbs.layout.section title="Kalender abonnieren">
        <p>Mit dieser Adresse erscheinen alle Vorstandssitzungen in Ihrem Kalenderprogramm (Outlook, Apple Kalender, Thunderbird, Google Kalender, Nextcloud) und aktualisieren sich von selbst. Tagesordnung und Protokoll stehen nicht darin.</p>
        <p><x-vdbs.actions.button variant="secondary" :href="$webcal" icon="calendar">Abonnieren</x-vdbs.actions.button></p>
        <x-vdbs.forms.input name="feed_copy" label="Abo-Adresse zum Kopieren" :value="$feed" readonly />
        <p class="vdbs-hint">Die Adresse ist geheim: Wer sie kennt, sieht die Termine. Geben Sie sie nur an den Vorstand weiter.</p>
        <form method="post" action="{{ route('board.meetings.feed.renew') }}">
            @csrf
            <x-vdbs.actions.button type="submit" variant="secondary" size="s" onclick="return confirm('Die Abo-Adresse erneuern? Die bisherige Adresse funktioniert danach nicht mehr.')">Adresse erneuern</x-vdbs.actions.button>
        </form>
    </x-vdbs.layout.section>

    <x-vdbs.layout.section title="Einladungen per E-Mail">
        <p>Mit eingeschalteten Einladungen kann der Vorstand eine Sitzung per E-Mail ankündigen, mit Termin, Tagesordnung und Kalenderdatei im Anhang. Die Einladung geht nur auf Knopfdruck hinaus, nie von selbst. @if ($invitations)Zurzeit erhalten {{ $recipientCount }} Vorstandsmitglieder sie.@endif</p>
        <form method="post" action="{{ route('board.meetings.invitations.toggle') }}">
            @csrf @method('put')
            <x-vdbs.forms.switch name="enabled" label="Einladungen per E-Mail" :checked="$invitations" hint="Ausgeschaltet gibt es auf den Sitzungsseiten keinen Knopf zum Einladen." />
            <x-vdbs.actions.button type="submit" variant="secondary" size="s" icon="save">Übernehmen</x-vdbs.actions.button>
        </form>
        <p class="vdbs-hint">Einzelne Vorstandsmitglieder können die Einladungen in ihren Benachrichtigungen (Konto) abstellen.</p>
    </x-vdbs.layout.section>

    <x-vdbs.layout.section title="Externer Kalender (optional)">
        @if ($externalUrl !== '')
            <p><x-vdbs.actions.button variant="secondary" :href="$externalUrl" icon="calendar" :external="true">Externen Kalender öffnen</x-vdbs.actions.button></p>
        @endif
        <x-vdbs.forms.form :action="route('board.meetings.calendar')" method="put">
            <x-vdbs.forms.input name="calendar_url" label="Link zu einem anderen gemeinsamen Kalender" type="url" :optional="true" maxlength="500" :value="old('calendar_url', $externalUrl)" hint="Wenn der Vorstand zusätzlich einen Kalender außerhalb des Portals nutzt, zum Beispiel in Nextcloud. Die Adresse muss mit https:// beginnen." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Link speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    </x-vdbs.layout.section>
</x-layouts.app>
