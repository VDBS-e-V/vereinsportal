@php
    use App\Support\Money;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Zahlungsaufträge']];
@endphp

<x-layouts.app title="Zahlungsaufträge" width="wide">
    <x-vdbs.layout.page-header title="Zahlungsaufträge" lead="Aus den Verträgen der Teamenden, hochgeladen von der Seminarleitung. Die Bankdaten stehen im Vertrag." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('seminars.payments.export')" icon="download">Offene als CSV</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @error('payment')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <p>Offen insgesamt: <strong>{{ Money::format($openSum) }}</strong></p>

    <x-vdbs.data.toolbar label="Zahlungsaufträge filtern" :search="false" :reset-href="route('seminars.payments.index')" :count="$orders->count().' '.($orders->count() === 1 ? 'Zahlungsauftrag' : 'Zahlungsaufträge')">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$status" />
    </x-vdbs.data.toolbar>

    @if ($orders->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Zahlungsaufträge" icon="wallet"><p>Sobald eine Seminarleitung einen Vertrag hochlädt, erscheint hier der Zahlungsauftrag.</p></x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Zahlungsaufträge" :columns="[['key' => 'seminar', 'label' => 'Seminar'], ['key' => 'person', 'label' => 'Person'], ['key' => 'amount', 'label' => 'Betrag'], ['key' => 'contract', 'label' => 'Vertrag'], ['key' => 'status', 'label' => 'Status'], ['key' => 'actions', 'label' => 'Entscheidung']]">
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->seminar->number }} · {{ $order->seminar->title }}</td>
                    <td>{{ $order->user->person?->fullName() ?: $order->user->email }}</td>
                    <td>{{ Money::format($order->amount_cents) }}</td>
                    <td>@if ($order->document)<a href="{{ route('seminars.documents.download', $order->document) }}">PDF</a>@endif</td>
                    <td>{{ $order->statusLabel() }}@if (filled($order->note)): {{ $order->note }}@endif</td>
                    <td>
                        @if ($order->status !== 'paid')
                            <form method="post" action="{{ route('seminars.payments.decide', $order) }}">@csrf
                                <label class="vdbs-sr-only" for="note-{{ $order->id }}">Anmerkung zu {{ $order->user->person?->fullName() ?: $order->user->email }}</label>
                                <input id="note-{{ $order->id }}" class="vdbs-control" name="note" placeholder="Anmerkung (bei Ablehnung Pflicht)" maxlength="2000">
                                <button class="vdbs-btn vdbs-btn--secondary vdbs-btn--s" type="submit" name="status" value="paid">Bezahlt</button>
                                <button class="vdbs-btn vdbs-btn--text vdbs-btn--s" type="submit" name="status" value="rejected">Ablehnen</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
