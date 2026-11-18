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

    @error('team')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @php
        $applications = $seminar->applications;
        $selecting = in_array($seminar->status, [SeminarStatus::Tendered, SeminarStatus::TeamSelected], true);
        $teamPhase = in_array($seminar->status, [SeminarStatus::TeamConfirmed, SeminarStatus::Prepared], true);
        $active = $applications->where('status', 'applied');
        $confirms = $active->where('decision', 'confirm');
        $personName = fn ($application): string => $application->user->person?->fullName() ?: (string) $application->user->email;
        $decisionLabel = ['confirm' => 'Zusage', 'decline' => 'Absage'];
        $outcomeLabel = ['confirmed' => 'Zusage', 'declined' => 'Absage', 'waitlist' => 'Warteliste', 'dropped' => 'Ausgeschieden'];
    @endphp

    @if ($selecting && $applications->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Auswahl des Teams ({{ $confirms->count() }} von {{ $seminar->team_target }} Zusagen im Entwurf)</h2>
        <p>Zusage und Absage sind zunächst nur ein Entwurf. Erst „Team bestätigen“ sendet alle Zusagen, Absagen und Wartelistenplätze zugleich.</p>

        <x-vdbs.data.table caption="Interessierte Teamende" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'availability', 'label' => 'Verfügbarkeit'], ['key' => 'draft', 'label' => 'Entwurf'], ['key' => 'actions', 'label' => 'Entscheidung']]">
            @foreach ($active as $application)
                <tr>
                    <td>{{ $personName($application) }}</td>
                    <td>{{ $application->availability }}</td>
                    <td>{{ $decisionLabel[$application->decision] ?? 'offen (Warteliste)' }}</td>
                    <td>
                        @foreach (['confirm' => 'Zusage', 'decline' => 'Absage', 'reset' => 'Zurücksetzen'] as $value => $label)
                            <form method="post" action="{{ route('seminars.plan.decide', [$seminar, $application]) }}" style="display:inline">@csrf
                                <input type="hidden" name="decision" value="{{ $value }}">
                                <x-vdbs.actions.button type="submit" size="s" :variant="$value === 'reset' ? 'text' : 'secondary'">{{ $label }}</x-vdbs.actions.button>
                            </form>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        @if ($seminar->status === SeminarStatus::TeamSelected)
            <x-vdbs.forms.form :action="route('seminars.plan.confirm-team', $seminar)" :summary="false" style="margin-top:var(--space-5)">
                <x-vdbs.forms.select name="lead" label="Seminarleitung" placeholder="Bitte wählen" :required="true" :options="$confirms->mapWithKeys(fn ($application): array => [$application->user_id => $personName($application)])->all()" :value="old('lead')" />
                @if ($confirms->count() < $seminar->team_target)
                    <x-vdbs.forms.checkbox name="acknowledge" label="Es sind weniger Zusagen als die Sollzahl. Ich bestätige das Team trotzdem." value="1" />
                @endif
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Team bestätigen und Mails senden</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @elseif ($applications->isNotEmpty() && ! $selecting)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Team</h2>
        <x-vdbs.data.table caption="Team und Warteliste" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'outcome', 'label' => 'Ergebnis'], ['key' => 'actions', 'label' => 'Aktion']]">
            @foreach ($applications->whereNotNull('outcome')->sortBy(fn ($application): string => ($application->outcome === 'confirmed' ? '0' : ($application->outcome === 'waitlist' ? '1' : '2')).str_pad((string) $application->waitlist_position, 4, '0', STR_PAD_LEFT)) as $application)
                <tr>
                    <td>{{ $personName($application) }}@if ($seminar->lead_user_id === $application->user_id) <x-vdbs.data.badge tone="success">Seminarleitung</x-vdbs.data.badge>@endif</td>
                    <td>{{ $outcomeLabel[$application->outcome] ?? $application->outcome }}@if ($application->outcome === 'waitlist') (Platz {{ $application->waitlist_position }})@endif</td>
                    <td>
                        @if ($teamPhase && $application->outcome === 'waitlist')
                            <form method="post" action="{{ route('seminars.plan.promote', [$seminar, $application]) }}" style="display:inline">@csrf
                                <x-vdbs.actions.button type="submit" size="s" variant="secondary">Nachrücken lassen</x-vdbs.actions.button>
                            </form>
                        @elseif ($teamPhase && $application->outcome === 'confirmed')
                            <form method="post" action="{{ route('seminars.plan.drop', [$seminar, $application]) }}" style="display:inline">@csrf
                                <label class="vdbs-sr-only" for="drop-{{ $application->id }}">Begründung für das Ausscheiden von {{ $personName($application) }}</label>
                                <input id="drop-{{ $application->id }}" class="vdbs-control" name="reason" placeholder="Begründung" maxlength="2000" required>
                                <x-vdbs.actions.button type="submit" size="s" variant="danger-text">Ausscheiden</x-vdbs.actions.button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        @if ($teamPhase)
            <x-vdbs.forms.form :action="route('seminars.plan.lead', $seminar)" :summary="false" style="margin-top:var(--space-5)">
                <x-vdbs.forms.select name="lead" label="Seminarleitung ändern" placeholder="Bitte wählen" :required="true" :options="$applications->where('outcome', 'confirmed')->mapWithKeys(fn ($application): array => [$application->user_id => $personName($application)])->all()" :value="$seminar->lead_user_id" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary">Seminarleitung festlegen</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @endif

    @if ($teamPhase)
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Vorbereitung</h2>

        <x-vdbs.forms.form :action="route('seminars.plan.onboarding', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="onboarding_at" label="Onboarding-Termin" type="datetime-local" :required="true" :value="old('onboarding_at', $seminar->onboarding_at?->format('Y-m-d\TH:i'))" />
                <x-vdbs.forms.input name="onboarding_link" label="Link zur Videokonferenz" :optional="true" maxlength="500" :value="old('onboarding_link', $seminar->onboarding_link)" hint="Mit https:// beginnend." />
            </x-vdbs.forms.form-grid>
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="mail">{{ $seminar->onboarding_at ? 'Termin ändern und Team erneut einladen' : 'Termin festlegen und Team einladen' }}</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>

        <x-vdbs.forms.form :action="route('seminars.plan.briefing', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.textarea id="briefing-text" name="text" label="Briefing an die Schule (Ablauf, Raum, Material)" :required="true" maxlength="3000" />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="mail">Briefing senden</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
        @if ($seminar->briefing_sent_at)<p class="vdbs-hint">Briefing gesendet am {{ $seminar->briefing_sent_at->format('d.m.Y') }}.</p>@endif

        @if ($seminar->status === SeminarStatus::TeamConfirmed)
            <x-vdbs.forms.form :action="route('seminars.plan.prepare', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
                <p>Das Seminar ist vorbereitet, wenn das Team bestätigt, die Seminarleitung bestimmt und der Onboarding-Termin gesetzt ist.</p>
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Als vorbereitet markieren</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @endif
    @endif

    @if (in_array($seminar->status, [SeminarStatus::Held, SeminarStatus::Reported, SeminarStatus::Completed], true))
        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Bericht und Abschluss</h2>
        @php $docs = $seminar->documents()->whereIn('kind', ['report', 'team_contract', 'material', 'other'])->get(); @endphp
        <ul>
            @foreach ($docs as $document)
                <li>{{ $document->kindLabel() }}: <a href="{{ route('seminars.documents.download', $document) }}">{{ $document->original_name }}</a></li>
            @endforeach
        </ul>
        @if ($seminar->status === SeminarStatus::Held)
            <p>Die Seminarleitung gibt Bericht und Verträge bis {{ $seminar->report_due_on?->format('d.m.Y') }} ab.</p>
        @elseif ($seminar->status === SeminarStatus::Reported)
            @if (! $seminar->report_sent_at)
                <x-vdbs.forms.form :action="route('seminars.plan.send-results', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
                    <x-vdbs.forms.form-grid :columns="2">
                        <x-vdbs.forms.input name="results_url" label="Link zum Ergebnisordner" :required="true" maxlength="500" :value="old('results_url', $seminar->results_url)" hint="Mit https:// beginnend." />
                        <x-vdbs.forms.input name="results_expires_on" label="Link erreichbar bis" type="date" :required="true" :value="old('results_expires_on', $seminar->sessions->max('starts_at')?->copy()->addYears((int) config('portal.seminars.results_years', 2))->format('Y-m-d'))" hint="Mindestens ein Jahr nach dem letzten Termin." />
                    </x-vdbs.forms.form-grid>
                    <x-slot:actions><x-vdbs.actions.button type="submit" icon="send">Bericht und Link an die Schule senden</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            @else
                <p>Bericht und Link sind am {{ $seminar->report_sent_at->format('d.m.Y') }} an die Schule gesendet (Link bis {{ $seminar->results_expires_on?->format('d.m.Y') }}).</p>
                <x-vdbs.forms.form :action="route('seminars.plan.complete', $seminar)" :summary="false" style="margin-bottom:var(--space-5)">
                    <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Seminar abschließen</x-vdbs.actions.button></x-slot:actions>
                </x-vdbs.forms.form>
            @endif

            <x-vdbs.forms.form :action="route('seminars.plan.return-report', $seminar)" :summary="false">
                <x-vdbs.forms.textarea id="return-comment" name="comment" label="Zurück an die Seminarleitung: Was fehlt oder ist zu ändern?" :optional="true" maxlength="2000" />
                <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary">Zur Überarbeitung zurückgeben</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>
        @else
            <p>Das Seminar ist am {{ $seminar->completed_at?->format('d.m.Y') }} abgeschlossen.</p>
        @endif
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
