@php
    use App\Modules\Seminars\Enums\SeminarStatus;
    use App\Support\Money;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Meine Leitung', 'href' => route('seminars.lead.index')], ['label' => $seminar->number]];
    $open = in_array($seminar->status, [SeminarStatus::Prepared, SeminarStatus::Held], true);
    $team = $seminar->applications->where('outcome', 'confirmed');
    $orders = $seminar->paymentOrders->keyBy('user_id');
    $name = fn ($application): string => $application->user->person?->fullName() ?: (string) $application->user->email;
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="$seminar->number.' · '.$seminar->partner->name" :breadcrumb="$crumbs" />

    @error('lead')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <p><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge>
        @if ($seminar->report_due_on && $seminar->status === SeminarStatus::Held) Bericht und Verträge sind bis {{ $seminar->report_due_on->format('d.m.Y') }} fällig.@endif</p>

    <ul>
        @foreach ($seminar->sessions as $session)
            <li>{{ $session->starts_at->format('d.m.Y, H:i') }} Uhr{{ filled($session->location) ? ', '.$session->location : '' }}</li>
        @endforeach
    </ul>

    @if ($seminar->status === SeminarStatus::Prepared)
        <x-vdbs.forms.form :action="route('seminars.lead.held', $seminar)" :summary="false" style="margin:var(--space-5) 0">
            <p>Wenn das Seminar stattgefunden hat, bestätigen Sie die Durchführung. Das geht nach dem Beginn des letzten Termins.</p>
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check" :disabled="! $canHold">Durchführung bestätigen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    <p><a href="{{ route('seminars.files.planner', $seminar) }}">Druckplaner</a> · <a href="{{ route('seminars.files.folder', $seminar) }}">Seminarmappe als ZIP</a></p>

    @if ($open)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Verträge der Teamenden</h2>
        <p>Laden Sie je Person den ausgefüllten und unterschriebenen Vertrag hoch und tragen Sie den Betrag aus dem Vertrag ein. Daraus entsteht ein Zahlungsauftrag an die Geschäftsstelle.</p>

        <x-vdbs.data.table caption="Verträge der Teamenden" :columns="[['key' => 'name', 'label' => 'Person'], ['key' => 'contract', 'label' => 'Vertrag und Betrag'], ['key' => 'status', 'label' => 'Zahlungsauftrag']]">
            @foreach ($team as $application)
                @php $order = $orders->get($application->user_id); @endphp
                <tr>
                    <td>{{ $name($application) }}</td>
                    <td>@if ($order && $order->document)<a href="{{ route('seminars.documents.download', $order->document) }}">{{ $order->document->original_name }}</a>, {{ Money::format($order->amount_cents) }}@else fehlt noch @endif</td>
                    <td>{{ $order?->statusLabel() ?? '–' }}@if ($order?->note): {{ $order->note }}@endif</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <x-vdbs.forms.form :action="route('seminars.lead.contract', $seminar)" :files="true" :summary="false" style="margin-top:var(--space-5)">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.select name="member" label="Person" placeholder="Bitte wählen" :required="true" :options="$team->mapWithKeys(fn ($application): array => [$application->user_id => $name($application)])->all()" :value="old('member')" />
                <x-vdbs.forms.input name="amount" label="Betrag aus dem Vertrag in Euro" :required="true" inputmode="decimal" :value="old('amount')" />
                <x-vdbs.forms.file name="file" label="Ausgefüllter Vertrag" :required="true" />
            </x-vdbs.forms.form-grid>
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Vertrag hochladen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Ergebnisbericht und weitere Dateien</h2>
        <x-vdbs.forms.form :action="route('seminars.lead.upload', $seminar)" :files="true" :summary="false">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.select name="kind" label="Art" :required="true" :options="['report' => 'Ergebnisbericht', 'material' => 'Material', 'other' => 'Sonstiges']" :value="old('kind', 'report')" />
                <x-vdbs.forms.input name="title" label="Bezeichnung" :optional="true" maxlength="200" :value="old('title')" />
                <x-vdbs.forms.file name="file" label="Datei" :required="true" />
            </x-vdbs.forms.form-grid>
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="upload">Datei hochladen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        @php $files = $seminar->documents->whereIn('kind', ['report', 'material', 'other']); @endphp
        @if ($files->isNotEmpty())
            <ul>
                @foreach ($files as $document)
                    <li>{{ $document->kindLabel() }}: <a href="{{ route('seminars.documents.download', $document) }}">{{ $document->original_name }}</a></li>
                @endforeach
            </ul>
        @endif
    @endif

    @if ($seminar->status === SeminarStatus::Held)
        <x-vdbs.forms.form :action="route('seminars.lead.submit', $seminar)" :summary="false" style="margin-top:var(--space-6)">
            <p>Wenn Ergebnisbericht und alle Verträge hochgeladen sind, geben Sie sie ab. Die Koordination prüft sie.</p>
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Bericht und Verträge abgeben</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @elseif (in_array($seminar->status, [SeminarStatus::Reported, SeminarStatus::Completed], true))
        <x-vdbs.feedback.alert variant="success">Bericht und Verträge sind abgegeben{{ $seminar->reported_at ? ' am '.$seminar->reported_at->format('d.m.Y') : '' }}. Vielen Dank.</x-vdbs.feedback.alert>
    @endif
</x-layouts.app>
