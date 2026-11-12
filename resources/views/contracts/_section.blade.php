@php
    $rows = app(\App\Modules\Contracts\Services\Contracts::class)->overview($seminar);
    $money = fn ($cents) => \App\Modules\Seminars\Support\Money::format((int) $cents);
@endphp

@if ($rows !== [])
<section aria-labelledby="contracts-heading" style="margin-top:var(--space-8)">
    <h2 id="contracts-heading" style="font-size:var(--font-size-h3)">Verträge</h2>
    <p>Dienstleistungsverträge für das bestätigte Team. Das Portal erzeugt sie nur zum Ausdrucken; unterschrieben wird auf Papier an den Seminartagen. Danach legen Sie den Scan hier ab. Fehlende Angaben bleiben als Linie zum Ausfüllen von Hand.</p>

    <x-vdbs.data.table caption="Verträge des Seminars" :columns="[['key' => 'select', 'label' => 'Auswahl', 'selectAll' => 'contracts-form'], ['key' => 'name', 'label' => 'Person'], ['key' => 'number', 'label' => 'Vertrag'], ['key' => 'status', 'label' => 'Status'], ['key' => 'amount', 'label' => 'Vergütung'], ['key' => 'actions', 'label' => 'Aktionen']]">
        @foreach ($rows as $row)
            @php $application = $row['application']; $contract = $row['contract']; @endphp
            <tr>
                <td class="vdbs-table__select">
                    @if ($contract === null)
                        <span class="vdbs-choice vdbs-choice--checkbox"><input class="vdbs-choice__input" type="checkbox" name="applications[]" value="{{ $application->id }}" form="contracts-form" aria-label="{{ $application->applicantName() }} auswählen"></span>
                    @endif
                </td>
                <td>{{ $application->applicantName() }}</td>
                <td>{{ $contract?->number ?? '–' }}</td>
                <td>
                    @if ($contract)
                        <x-vdbs.data.badge :tone="$contract->status === 'signed' ? 'success' : 'neutral'">{{ $contract->statusLabel() }}</x-vdbs.data.badge>
                    @else
                        <x-vdbs.data.badge tone="warning">Noch kein Vertrag</x-vdbs.data.badge>
                    @endif
                </td>
                <td>{{ $contract ? ($contract->data['amount_cents'] ?? 0 ? $money($contract->data['amount_cents']) : 'offen') : '–' }}</td>
                <td>
                    @if ($contract)
                        <x-vdbs.actions.button size="s" variant="text" :href="route('contracts.show', [$seminar, $contract])">Ansehen und drucken</x-vdbs.actions.button>
                        @if ($contract->isEditable())
                            <x-vdbs.actions.button size="s" variant="text" :href="route('contracts.edit', [$seminar, $contract])">Bearbeiten</x-vdbs.actions.button>
                            <form method="post" action="{{ route('contracts.scan', [$seminar, $contract]) }}" enctype="multipart/form-data" style="display:inline">@csrf
                                <input type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png" required aria-label="Unterschriebenen Scan für {{ $application->applicantName() }} wählen">
                                <x-vdbs.actions.button size="s" variant="secondary" type="submit">Scan ablegen</x-vdbs.actions.button>
                            </form>
                            <form method="post" action="{{ route('contracts.cancel', [$seminar, $contract]) }}" style="display:inline">@csrf<x-vdbs.actions.button size="s" variant="danger-text" type="submit">Stornieren</x-vdbs.actions.button></form>
                        @elseif ($contract->signedDocument)
                            <x-vdbs.actions.button size="s" variant="text" :href="route('seminars.documents.download', [$seminar, $contract->signedDocument])">Scan</x-vdbs.actions.button>
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
    </x-vdbs.data.table>

    <form id="contracts-form" method="post" action="{{ route('contracts.store', $seminar) }}" style="margin-top:var(--space-3);display:flex;gap:var(--space-3);flex-wrap:wrap">@csrf
        <p class="vdbs-table__select-count" role="status" data-vdbs-select-count="contracts-form">Nichts ausgewählt.</p>
        <x-vdbs.actions.button type="submit" icon="file-text">Verträge für die Auswahl erzeugen</x-vdbs.actions.button>
        <x-vdbs.actions.button type="submit" variant="secondary" name="all" value="1">Alle Verträge erzeugen</x-vdbs.actions.button>
        <x-vdbs.actions.button variant="secondary" icon="printer" :href="route('contracts.print', $seminar)">Alle zum Drucken</x-vdbs.actions.button>
    </form>
</section>
@endif
