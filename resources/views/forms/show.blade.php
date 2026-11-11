@php
    $crumbs = $office
        ? [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Anträge', 'href' => route('forms-office.index')], ['label' => $application->number]]
        : [['label' => 'Start', 'href' => route('home')], ['label' => \App\Modules\Forms\Support\FormTypes::audience($audience)['label']], ['label' => 'Anträge', 'href' => route($audience.'-forms.index')], ['label' => $application->number]];
    $fileRoute = $office ? 'forms-office.file' : $audience.'-forms.file';
@endphp
<x-layouts.app :title="$application->typeLabel().' '.$application->number" width="wide">
    <x-vdbs.layout.page-header :title="$application->typeLabel().' '.$application->number" :lead="$application->purpose" :breadcrumb="$crumbs" />

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Stand"><x-vdbs.data.badge>{{ $application->statusLabel() }}</x-vdbs.data.badge></x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Summe">{{ $application->euro() }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Antragsteller:in">{{ $application->applicant_name }}@if ($office && $application->applicant_email) · {{ $application->applicant_email }}@endif</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Seminar">{{ $application->seminar ? trim(($application->seminar->number ? $application->seminar->number.' ' : '').$application->seminar->title) : '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Kostenstelle">{{ $application->cost_center ?: '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Eingereicht">{{ $application->submitted_at?->format('d.m.Y') ?? '–' }}</x-vdbs.data.detail-item>
        @if ($office)
            <x-vdbs.data.detail-item label="Freigabe durch">{{ $application->approval_level === 'board' ? 'Geschäftsführung oder Vorstand (über '.number_format($limit / 100, 2, ',', '.').' €)' : 'Geschäftsstelle' }}</x-vdbs.data.detail-item>
            @if ($iban) <x-vdbs.data.detail-item label="Konto">{{ $application->account_holder }} · {{ $iban }}</x-vdbs.data.detail-item> @endif
        @endif
        @if ($application->paid_at) <x-vdbs.data.detail-item label="Ausgezahlt">{{ $application->paid_at->format('d.m.Y') }}{{ $application->paid_reference ? ' · '.$application->paid_reference : '' }}</x-vdbs.data.detail-item> @endif
    </x-vdbs.data.detail-list>

    @if ($application->rejection_note && in_array($application->status, ['returned', 'rejected'], true))
        <x-vdbs.feedback.alert variant="warning" title="{{ $application->status === 'rejected' ? 'Begründung der Ablehnung' : 'Rückfrage' }}"><p>{{ $application->rejection_note }}</p></x-vdbs.feedback.alert>
    @endif
    @if ($application->exception_by_user_id) <x-vdbs.feedback.alert variant="info">Ausnahme bei der Belegfrist: {{ $application->exception_reason }}</x-vdbs.feedback.alert> @endif

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-6)">Positionen</h2>
    <x-vdbs.data.table caption="Positionen" :columns="[['key' => 'n', 'label' => 'Nr.'], ['key' => 'date', 'label' => 'Datum'], ['key' => 'kind', 'label' => 'Art'], ['key' => 'desc', 'label' => 'Beschreibung'], ['key' => 'amount', 'label' => 'Betrag'], ['key' => 'file', 'label' => 'Beleg']]">
        @foreach ($application->items as $item)
            <tr>
                <td>{{ $item->position }}</td>
                <td>{{ $item->date->format('d.m.Y') }}</td>
                <td>{{ $item->kindLabel() }}</td>
                <td>{{ $item->description }}</td>
                <td style="white-space:nowrap">{{ number_format($item->amount_cents / 100, 2, ',', '.') }} €</td>
                <td>@foreach ($item->files as $file)<a href="{{ route($fileRoute, [$application, $file]) }}">{{ $file->original_name }}</a>@if (! $loop->last), @endif @endforeach</td>
            </tr>
        @endforeach
    </x-vdbs.data.table>

    @if (! $office && $application->type === 'purchase')
        @if (! empty($application->data['reimbursement']))
            <p>Erstattung zu dieser Bestellung: <strong>{{ $application->data['reimbursement'] }}</strong></p>
        @elseif (in_array($application->status, ['approved', 'ordered', 'delivered', 'closed'], true))
            <form method="post" action="{{ route($audience.'-forms.reimburse', $application) }}" style="margin-bottom:var(--space-4)">@csrf
                <x-vdbs.actions.button type="submit" variant="secondary" icon="file-text">Aus eigener Tasche bezahlt: Erstattung beantragen</x-vdbs.actions.button>
                <p class="vdbs-hint">Legt einen Erstattungsantrag mit den Positionen dieser Bestellung an. Belege und Bankverbindung ergänzen Sie dort.</p>
            </form>
        @endif
    @endif
    @if (! $office && ! empty($application->data['from_purchase']))
        <p>Erstattung zur Bestellung <strong>{{ $application->data['from_purchase'] }}</strong>.</p>
    @endif
    @if ($office)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-6)">Bearbeiten</h2>
        @if (session('warning')) <x-vdbs.feedback.alert variant="warning">{{ session('warning') }}</x-vdbs.feedback.alert> @endif
        <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);margin-bottom:var(--space-4)">
            @if ($can['check'] && $application->status === 'submitted')
                <form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="review"><x-vdbs.actions.button type="submit" variant="secondary">In Prüfung nehmen</x-vdbs.actions.button></form>
            @endif
            @if ($can['check'] && $application->checked_at === null)
                <form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="check"><x-vdbs.actions.button type="submit" variant="secondary" icon="check">Prüfung abschließen</x-vdbs.actions.button></form>
            @endif
            @if ($can['approve'])
                <form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="approve"><x-vdbs.actions.button type="submit" icon="check">Freigeben</x-vdbs.actions.button></form>
            @endif
        </div>
        @if ($application->checked_at) <p class="vdbs-hint">Geprüft am {{ $application->checked_at->format('d.m.Y') }}{{ $application->self_checked ? ' (Selbstprüfung)' : '' }}.</p> @endif

        @if ($can['check'] || $can['approve'])
            <form method="post" action="{{ route('forms-office.act', $application) }}" style="margin-bottom:var(--space-4)">@csrf
                <x-vdbs.forms.input name="note" id="note-return" label="Rückfrage oder Begründung" :optional="true" maxlength="500" />
                <x-vdbs.actions.button type="submit" name="action" value="return" variant="secondary" icon="send">Rückfrage stellen</x-vdbs.actions.button>
                <x-vdbs.actions.button type="submit" name="action" value="reject" variant="secondary" icon="x">Ablehnen</x-vdbs.actions.button>
            </form>
        @endif
        @if ($can['pay'])
            <form method="post" action="{{ route('forms-office.act', $application) }}" style="margin-bottom:var(--space-4)">@csrf<input type="hidden" name="action" value="paid">
                <x-vdbs.forms.input name="note" id="note-paid" label="Verweis zur Auszahlung (freiwillig)" :optional="true" maxlength="100" />
                <x-vdbs.actions.button type="submit" icon="check">Als ausgezahlt vermerken</x-vdbs.actions.button>
            </form>
        @endif
        @if ($can['purchase'])
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);margin-bottom:var(--space-4)">
                @if ($application->status === 'approved')<form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="ordered"><x-vdbs.actions.button type="submit">Als bestellt vermerken</x-vdbs.actions.button></form>@endif
                @if ($application->status === 'ordered')<form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="delivered"><x-vdbs.actions.button type="submit">Als geliefert vermerken</x-vdbs.actions.button></form>@endif
                @if ($application->status === 'delivered')<form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="close"><x-vdbs.actions.button type="submit" icon="check">Abschließen</x-vdbs.actions.button></form>@endif
            </div>
            @if (in_array($application->status, ['ordered', 'delivered'], true))
                <form method="post" action="{{ route('forms-office.invoice', $application) }}" enctype="multipart/form-data" style="margin-bottom:var(--space-4)">@csrf
                    <label for="invoice" style="display:block;font-weight:600">Rechnung anhängen (PDF, JPEG, PNG)</label>
                    <input id="invoice" type="file" name="invoice" accept="application/pdf,image/jpeg,image/png">
                    <x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Rechnung hochladen</x-vdbs.actions.button>
                </form>
            @endif
        @endif
        @if ($can['exception'])
            <form method="post" action="{{ route('forms-office.act', $application) }}">@csrf<input type="hidden" name="action" value="exception">
                <x-vdbs.forms.input name="note" id="note-exception" label="Ausnahme bei der Belegfrist: Begründung" :optional="true" maxlength="300" />
                <x-vdbs.actions.button type="submit" variant="secondary">Ausnahme freischalten</x-vdbs.actions.button>
            </form>
        @endif
    @endif

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-6)">Verlauf</h2>
    <x-vdbs.data.timeline label="Verlauf des Antrags">
        @foreach ($application->events as $event)
            <x-vdbs.data.timeline-item :title="$event->label()" :time="$event->occurred_at->format('d.m.Y, H:i')" :datetime="$event->occurred_at->toIso8601String()" :actor="$event->actor_label ?? 'automatisch'" tone="neutral" icon="clock">{{ $event->comment }}</x-vdbs.data.timeline-item>
        @endforeach
    </x-vdbs.data.timeline>
</x-layouts.app>
