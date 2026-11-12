@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Anträge']])
<x-layouts.app title="Anträge" width="wide">
    <x-vdbs.layout.page-header title="Anträge" :lead="$open.' Antrag/Anträge warten auf Prüfung oder Freigabe.'" :breadcrumb="$crumbs">
        <x-slot:actions>
            @if ($canManage)
                <x-vdbs.actions.button variant="secondary" :href="route('forms-office.cost-centers')">Kostenstellen</x-vdbs.actions.button>
                <x-vdbs.actions.button variant="secondary" :href="route('forms-office.settings')">Einstellungen</x-vdbs.actions.button>
            @endif
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.data.toolbar label="Anträge filtern" :count="$applications->total().' Anträge'" :reset-href="route('forms-office.index')" search-placeholder="Nummer, Name oder Zweck suchen">
        <x-vdbs.forms.multi-select name="status" label="Stand" placeholder="Alle Stände" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.multi-select name="art" label="Art" placeholder="Alle Arten" :options="$typeOptions" :value="$filters['art']" />
    </x-vdbs.data.toolbar>

    @if ($applications->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Anträge" icon="info"><p>Zu den gewählten Filtern gibt es nichts.</p></x-vdbs.feedback.empty-state>
    @else
        @if ($canManage) <form id="export-form" method="post" action="{{ route('forms-office.export') }}">@csrf</form> @endif
        <x-vdbs.data.table caption="Anträge" :columns="array_merge($canManage ? [['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'export-form']] : [], [['key' => 'number', 'label' => 'Nummer'], ['key' => 'type', 'label' => 'Art'], ['key' => 'who', 'label' => 'Antragsteller:in'], ['key' => 'total', 'label' => 'Summe'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'since', 'label' => 'Eingereicht']])">
            @foreach ($applications as $application)
                <tr>
                    @if ($canManage)
                        <td class="vdbs-table__select">@if ($application->status === 'approved')<span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="ids[]" value="{{ $application->id }}" form="export-form" aria-label="{{ $application->number }} für die Sammelliste auswählen"></span>@endif</td>
                    @endif
                    <td style="white-space:nowrap"><a href="{{ route('forms-office.show', $application) }}">{{ $application->number }}</a></td>
                    <td>{{ $application->typeLabel() }}</td>
                    <td>{{ $application->applicant_name }}</td>
                    <td style="white-space:nowrap">{{ $application->euro() }}</td>
                    <td><x-vdbs.data.badge :tone="in_array($application->status, ['approved', 'paid'], true) ? 'success' : ($application->status === 'rejected' ? 'error' : 'neutral')">{{ $application->statusLabel() }}</x-vdbs.data.badge>@if ($application->exported_at && $application->status === 'approved') <span class="vdbs-hint">Sammelliste erstellt</span>@endif</td>
                    <td>{{ $application->submitted_at?->format('d.m.Y') ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
        {{ $applications->links() }}
        @if ($canManage) <p style="margin-top:var(--space-4)"><a href="{{ route('forms-office.export.bookings') }}">Buchungsliste (CSV)</a> · <a href="{{ route('forms-office.export.summary') }}">Jahresübersicht je Kostenstelle (CSV)</a> <span class="vdbs-hint">– Filter per Adresse: von, bis, art[], status[]</span></p> @endif
        @if ($canManage) <x-vdbs.actions.button type="submit" form="export-form" icon="download">Sammelliste für die Auszahlung herunterladen</x-vdbs.actions.button> <p class="vdbs-hint">Nur freigegebene Anträge. Die Liste enthält die IBAN; bitte nach dem Überweisen löschen.</p> @endif
    @endif
</x-layouts.app>
