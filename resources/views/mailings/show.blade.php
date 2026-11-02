@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => $area], ['label' => 'Rundschreiben', 'href' => route($base.'.index')], ['label' => 'Versand']];
    $sent = (int) ($counts['sent'] ?? 0);
    $failedCount = (int) ($counts['failed'] ?? 0);
    $pending = (int) ($counts['pending'] ?? 0) + (int) ($counts['sending'] ?? 0);
    $skipped = (int) ($counts['skipped'] ?? 0);
    $active = $mailing->isActive();
    $who = $mailing->sentBy?->person?->fullName() ?? $mailing->sentBy?->email ?? 'unbekannt';
    $meta = $mailing->created_at?->format('d.m.Y, H:i').' von '.$who;
@endphp

<x-layouts.app title="Versand" width="wide">
    <x-vdbs.layout.page-header :title="$mailing->subject" :lead="$meta" :breadcrumb="$crumbs">
        <x-slot:meta>
            <x-vdbs.data.badge :tone="$mailing->statusTone()">{{ $mailing->statusLabel() }}</x-vdbs.data.badge>
        </x-slot:meta>
        <x-slot:actions>
            @if ($active)
                <form method="post" action="{{ route($base.'.cancel', $mailing) }}">@csrf<x-vdbs.actions.button type="submit" variant="danger" icon="x">Versand abbrechen</x-vdbs.actions.button></form>
            @elseif ($failedCount > 0)
                <form method="post" action="{{ route($base.'.retry', $mailing) }}">@csrf<x-vdbs.actions.button type="submit" icon="refresh-cw">Fehlgeschlagene erneut versuchen</x-vdbs.actions.button></form>
            @endif
            @if ($hasCertificate && ! $active)
                <x-vdbs.actions.button variant="secondary" icon="printer" :href="route($base.'.certificate', $mailing)">Versandbescheinigung</x-vdbs.actions.button>
            @endif
            <x-vdbs.actions.button variant="secondary" :href="route($base.'.index')">Zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @if ($active)
        <x-vdbs.feedback.alert variant="info">Das Schreiben wird im Hintergrund gesendet. Diese Seite aktualisiert sich alle paar Sekunden. Sie können sie schließen, der Versand läuft weiter. Läuft kein Worker (<code>php artisan queue:work</code>), bleibt das Schreiben in der Warteschlange.</x-vdbs.feedback.alert>
    @endif

    <x-vdbs.layout.grid :columns="4">
        <x-vdbs.data.stat label="Empfänger" :value="$mailing->recipients_count" icon="mail" />
        <x-vdbs.data.stat label="Gesendet" :value="$sent" tone="success" icon="check" />
        <x-vdbs.data.stat label="Offen" :value="$pending" :tone="$pending > 0 ? 'warning' : 'neutral'" icon="clock" />
        <x-vdbs.data.stat label="Fehlgeschlagen" :value="$failedCount" :tone="$failedCount > 0 ? 'error' : 'neutral'" icon="triangle-alert" :hint="$skipped > 0 ? $skipped.' übersprungen' : null" />
    </x-vdbs.layout.grid>

    <section aria-labelledby="progress-title" style="margin-top:var(--space-6)">
        <h2 id="progress-title" style="font-size:var(--font-size-h3)">Fortschritt</h2>
        <x-vdbs.feedback.progress :value="$percent" label="Versand in Prozent" />
        <p style="color:var(--text-muted)">{{ $percent }} % abgearbeitet.@if ($groupLabels) Gruppen: {{ $groupLabels }}.@endif</p>
    </section>

    @if ($failedCount > 0)
        <section aria-labelledby="failed-title" style="margin-top:var(--space-6)">
            <h2 id="failed-title" style="font-size:var(--font-size-h3)">Fehlgeschlagene Zustellungen</h2>
            @if (! $canSeeAddresses)
                <p>Die Adressen sehen nur die Person, die das Schreiben gesendet hat, und der Vorstand.</p>
            @elseif ($failed->isEmpty())
                <p>Die Einzelheiten sind nicht mehr gespeichert (Aufbewahrung {{ config('portal.mailings.delivery_retention_days') }} Tage).</p>
            @else
                <x-vdbs.data.table caption="Fehlgeschlagene Zustellungen" :columns="[['key' => 'email', 'label' => 'Adresse'], ['key' => 'attempts', 'label' => 'Versuche'], ['key' => 'error', 'label' => 'Grund']]">
                    @foreach ($failed as $delivery)
                        <tr>
                            <td>{{ $delivery->email }}</td>
                            <td>{{ $delivery->attempts }}</td>
                            <td>{{ $delivery->error ?? '–' }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
            @endif
        </section>
    @endif

    @if ($unreachable !== null)
        <section aria-labelledby="unreachable-title" style="margin-top:var(--space-6)">
            <h2 id="unreachable-title" style="font-size:var(--font-size-h3)">Nicht erreichte Mitglieder</h2>
            @if ($unreachable === [])
                <p>Alle laufenden Mitgliedschaften hatten eine zustellbare Adresse.</p>
            @else
                <x-vdbs.feedback.alert variant="info">{{ count($unreachable) }} Mitglieder haben dieses Schreiben nicht per E-Mail bekommen. Bitte laden Sie sie, wenn es nötig ist, postalisch ein. Die Liste zeigt den heutigen Stand der Mitgliedschaften.</x-vdbs.feedback.alert>
                <x-vdbs.data.table caption="Nicht erreichte Mitglieder" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'reason', 'label' => 'Grund']]">
                    @foreach ($unreachable as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['reason'] }}</td>
                        </tr>
                    @endforeach
                </x-vdbs.data.table>
            @endif
        </section>
    @endif

    @if (! $detailsKept)
        <p style="margin-top:var(--space-6);color:var(--text-muted)">Die Zustellliste mit den Adressen ist nach {{ config('portal.mailings.delivery_retention_days') }} Tagen gelöscht. Betreff, Text und Zahlen bleiben erhalten.</p>
    @endif

    @if ($active)
        <script>
            setTimeout(function () { window.location.reload(); }, 5000);
        </script>
    @endif
</x-layouts.app>
