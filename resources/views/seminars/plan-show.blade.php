@php
    use App\Modules\Seminars\Enums\SeminarStatus;

    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Seminare', 'href' => route('seminars.plan.index')], ['label' => $seminar->number]];
    $planEditable = in_array($seminar->status, [SeminarStatus::Released, SeminarStatus::Planned], true);
    $sessions = $seminar->sessions->values();
@endphp

<x-layouts.app :title="$seminar->title" width="wide">
    <x-vdbs.layout.page-header :title="$seminar->title" :lead="$seminar->number.' · '.$seminar->partner->name" :breadcrumb="$crumbs" />

    <p><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></p>

    <x-vdbs.data.detail-list :columns="2">
        <x-vdbs.data.detail-item label="Schule">{{ $seminar->partner->name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Koordination">{{ $seminar->coordination?->name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Format">{{ $seminar->format }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Teilnehmende">{{ $seminar->participants }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Schuljahr">{{ $seminar->school_year }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Sollzahl des Teams">{{ $seminar->team_target }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Bewerbungsfrist">{{ $seminar->application_deadline?->format('d.m.Y') }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gewünschter Zeitraum der Schule">{{ $seminar->desired_period }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ansprechperson der Schule">{{ $seminar->contact_name }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Anmerkungen der Schule">{{ $seminar->request_note }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    @error('plan')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @if ($planEditable)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Termine und Team</h2>
        <x-vdbs.forms.form :action="route('seminars.plan.plan', $seminar)">
            <x-vdbs.forms.input name="team_target" label="Sollzahl des Teams" type="number" :required="true" :value="old('team_target', $seminar->team_target)" />
            @for ($i = 0; $i < $rows; $i++)
                @php $session = $sessions->get($i); @endphp
                <x-vdbs.forms.form-grid :columns="4">
                    <x-vdbs.forms.input :id="'s-'.$i.'-date'" :name="'sessions['.$i.'][date]'" label="Datum" type="date" :optional="true" :value="old('sessions.'.$i.'.date', $session?->starts_at->format('Y-m-d'))" />
                    <x-vdbs.forms.input :id="'s-'.$i.'-from'" :name="'sessions['.$i.'][from]'" label="Beginn" type="time" :optional="true" :value="old('sessions.'.$i.'.from', $session?->starts_at->format('H:i'))" />
                    <x-vdbs.forms.input :id="'s-'.$i.'-to'" :name="'sessions['.$i.'][to]'" label="Ende" type="time" :optional="true" :value="old('sessions.'.$i.'.to', $session?->ends_at?->format('H:i'))" />
                    <x-vdbs.forms.input :id="'s-'.$i.'-loc'" :name="'sessions['.$i.'][location]'" label="Ort" :optional="true" maxlength="200" :value="old('sessions.'.$i.'.location', $session?->location)" />
                </x-vdbs.forms.form-grid>
            @endfor
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">{{ $seminar->status === SeminarStatus::Planned ? 'Änderungen speichern' : 'Planen' }}</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @elseif ($sessions->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Termine</h2>
        <ul>
            @foreach ($sessions as $session)
                <li>{{ $session->starts_at->format('d.m.Y, H:i') }}@if ($session->ends_at) bis {{ $session->ends_at->format('H:i') }}@endif Uhr{{ filled($session->location) ? ', '.$session->location : '' }}</li>
            @endforeach
        </ul>
    @endif

    @if ($seminar->status === SeminarStatus::Planned)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Ausschreiben</h2>
        <x-vdbs.forms.form :action="route('seminars.plan.tender', $seminar)" :summary="false">
            <x-vdbs.forms.input name="application_deadline" label="Bewerbungsfrist" type="date" :required="true" :value="old('application_deadline', $seminar->application_deadline?->format('Y-m-d'))" hint="Die Frist endet vor dem ersten Termin. Teamende melden sich bis dahin im Portal." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Für das Team ausschreiben</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @if ($seminar->applications->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Interessierte ({{ $seminar->applications->where('status', 'applied')->count() }})</h2>
        <x-vdbs.data.table caption="Interessierte Teamende" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'availability', 'label' => 'Verfügbarkeit'], ['key' => 'status', 'label' => 'Stand']]">
            @foreach ($seminar->applications as $application)
                <tr>
                    <td>{{ $application->user->person?->fullName() ?: $application->user->email }}</td>
                    <td>{{ $application->availability }}</td>
                    <td>{{ $application->status === 'applied' ? 'gemeldet' : 'zurückgenommen' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif

    @if ($canCancel)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Absagen</h2>
        <x-vdbs.forms.form :action="route('seminars.plan.cancel', $seminar)" :summary="false">
            <x-vdbs.forms.textarea id="cancel-comment" name="comment" label="Begründung der Absage" :required="true" maxlength="2000" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="danger">Seminar absagen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Verlauf</h2>
    <x-vdbs.data.timeline label="Verlauf des Seminars">
        @foreach ($seminar->events as $event)
            <x-vdbs.data.timeline-item :title="$event->describe()" :time="$event->created_at?->format('d.m.Y, H:i')" :actor="$event->actor_label">
                @if (filled($event->comment)){{ $event->comment }}@endif
            </x-vdbs.data.timeline-item>
        @endforeach
    </x-vdbs.data.timeline>
</x-layouts.app>
