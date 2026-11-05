@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminarstatistik']];
    $t = $report['totals'];
    $count = $t['seminars'] === 1 ? '1 Seminar' : $t['seminars'].' Seminare';
    $euro = fn (int $cents) => number_format($cents / 100, 2, ',', '.').' €';
@endphp

<x-layouts.app title="Seminarstatistik" width="wide">
    <x-vdbs.layout.page-header title="Seminarstatistik" lead="Alle abgeschlossenen Seminare mit den Kennzahlen aus dem Abschluss. Nur Zahlen, keine Personen." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminar-stats.export', request()->query())" icon="download">CSV herunterladen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="4" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Seminare" value="{{ $t['seminars'] }}" icon="calendar" />
        <x-vdbs.data.stat label="Termine" value="{{ $t['sessions'] }} ({{ str_replace('.', ',', (string) $t['hours']) }} Std.)" icon="clock" />
        <x-vdbs.data.stat label="Teamende (Plätze)" value="{{ $t['team_size'] }}" icon="users" />
        <x-vdbs.data.stat label="Kostenvoranschläge" value="{{ $euro($t['quote_cents']) }}" icon="clipboard-list" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Statistik filtern" :count="$count" :active="$tags" :reset-href="route('seminar-stats.index')" :search="false">
        <x-vdbs.forms.multi-select name="jahr" label="Jahr" placeholder="Alle Jahre" :options="$yearOptions" :value="$filters['years']" />
        <x-vdbs.forms.multi-select name="leistung" label="Leistung" placeholder="Alle Leistungen" :options="$typeOptions" :value="$filters['types']" />
        <x-vdbs.forms.multi-select name="schule" label="Schule" :searchable="true" placeholder="Alle Schulen" search-label="Schulen filtern" :options="$schoolOptions" :value="$filters['schools']" />
    </x-vdbs.data.toolbar>

    @if ($report['rows'] === [])
        <x-vdbs.feedback.empty-state title="Keine abgeschlossenen Seminare" icon="calendar">
            <p>Sobald Seminare abgeschlossen sind, erscheinen sie hier. Zu den gewählten Filtern gibt es nichts.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Abgeschlossene Seminare" :columns="[['key' => 'number', 'label' => 'Nr.'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'year', 'label' => 'Jahr'], ['key' => 'sessions', 'label' => 'Termine'], ['key' => 'hours', 'label' => 'Stunden'], ['key' => 'team', 'label' => 'Team'], ['key' => 'participants', 'label' => 'Teilnehmende'], ['key' => 'quote', 'label' => 'Kostenvoranschlag']]">
            @foreach ($report['rows'] as $row)
                <tr>
                    <td style="white-space:nowrap">{{ $row['number'] }}</td>
                    <td><a href="{{ route('seminars.show', $row['id']) }}">{{ $row['title'] }}</a><br><span class="vdbs-hint">{{ $row['type'] }}</span></td>
                    <td>{{ $row['school'] }}</td>
                    <td>{{ $row['year'] }}</td>
                    <td>{{ $row['sessions'] }}</td>
                    <td>{{ str_replace('.', ',', (string) $row['hours']) }}</td>
                    <td>{{ $row['team_size'] }}</td>
                    <td>{{ $row['participants'] ?: '–' }}</td>
                    <td style="white-space:nowrap">{{ $row['quote_cents'] ? $euro($row['quote_cents']) : '–' }}</td>
                </tr>
            @endforeach
            <tr>
                <th scope="row" colspan="4">Summe ({{ $count }})</th>
                <td><strong>{{ $t['sessions'] }}</strong></td>
                <td><strong>{{ str_replace('.', ',', (string) $t['hours']) }}</strong></td>
                <td><strong>{{ $t['team_size'] }}</strong></td>
                <td><strong>{{ $t['participants'] }}</strong></td>
                <td style="white-space:nowrap"><strong>{{ $euro($t['quote_cents']) }}</strong></td>
            </tr>
        </x-vdbs.data.table>
    @endif

    @if ($expiring->isNotEmpty())
        <section aria-labelledby="expiring-heading" style="margin-top:var(--space-8)">
            <h2 id="expiring-heading" style="font-size:var(--font-size-h3)">Dokumente, deren Aufbewahrungsfrist bald abläuft</h2>
            <p>Diese Dokumente werden in den nächsten 90 Tagen automatisch gelöscht. Laden Sie sie vorher herunter, wenn Sie sie noch brauchen.</p>
            <x-vdbs.data.table caption="Bald ablaufende Dokumente" :columns="[['key' => 'until', 'label' => 'Gelöscht am'], ['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'doc', 'label' => 'Dokument']]">
                @foreach ($expiring as $document)
                    <tr>
                        <td>{{ $document->retention_until->format('d.m.Y') }}</td>
                        <td><a href="{{ route('seminars.show', $document->seminar_id) }}">{{ $document->seminar?->number }} {{ $document->seminar?->title }}</a></td>
                        <td>{{ $document->kindLabel() }}: {{ $document->title }}</td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        </section>
    @endif
</x-layouts.app>
