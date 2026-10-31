@php
    $needed = (int) $seminar->team_needed;
    $summary = $review['summary'];
    $rows = collect($review['rows']);
    $deadline = $seminar->application_deadline;
    $passed = $seminar->deadlinePassed();
    $label = fn (string $key): string => $competenceLabels[$key] ?? $key;
    $statusLabel = $seminar->status->label();
@endphp

<section aria-labelledby="team-heading" style="margin-top:var(--space-8)">
    <h2 id="team-heading" style="font-size:var(--font-size-h3)">Team</h2>

    <p>
        @if ($needed > 0)
            Gesucht: <strong>{{ $needed }}</strong> Teamende, im Team: <strong>{{ $summary['size'] }}</strong>.
            @if ($deadline)
                Stichtag für Interesse: <strong>{{ $deadline->format('d.m.Y') }}</strong>
                @if ($passed) (vorbei, die Koordination kann jetzt sichten und das Team zusammenstellen). @else (bis dahin können Teamende Interesse bekunden; zusammengestellt wird danach). @endif
            @endif
            @unless ($seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Scheduled) Das Seminar erscheint erst im Status „Terminiert“ bei den Teamenden (jetzt: {{ $statusLabel }}). @endunless
            @if ($passed && $summary['size'] >= $needed && $seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Scheduled) Das Team ist vollständig: Sie können das Seminar jetzt bestätigen. @endif
        @else
            Noch kein Bedarf eingetragen. Mit Bedarf, Stichtag und dem Status „Terminiert“ erscheint das Seminar bei den Teamenden.
        @endif
    </p>

    @if ($canManage)
        <x-vdbs.forms.form :action="route('seminars.team.need', $seminar)" method="put" :summary="false">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.input name="team_needed" label="Bedarf an Teamenden" type="number" min="0" max="20" :optional="true" :value="$seminar->team_needed" hint="0 oder leer = kein Interesse mehr möglich" />
                <x-vdbs.forms.input name="application_deadline" label="Stichtag" type="date" :optional="true" :value="$deadline?->format('Y-m-d')" hint="Pflicht bei Bedarf. Bis zu diesem Tag (einschließlich) können Teamende Interesse bekunden." />
            </x-vdbs.forms.form-grid>
            <x-vdbs.forms.checkbox-group name="team_competences" legend="Gesuchte Kompetenzen" :optional="true" layout="inline" :options="$competenceLabels" :value="$seminar->team_competences ?? []" hint="Dienen als Kriterium beim Zusammenstellen und stehen bei den Teamenden." />
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="save">Bedarf und Stichtag speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @if ($rows->isNotEmpty())
        <h3 style="margin-top:var(--space-6);font-size:var(--font-size-h4)">Interessenbekundungen</h3>

        @if ($canManage && ! $passed && $deadline)
            <x-vdbs.feedback.alert variant="info">Der Stichtag ist noch nicht erreicht. Entscheiden können Sie danach (oder Sie setzen den Stichtag auf heute oder früher).</x-vdbs.feedback.alert>
        @endif

        <x-vdbs.data.table caption="Interessenbekundungen auf das Seminar" :columns="[['key' => 'person', 'label' => 'Person'], ['key' => 'profile', 'label' => 'Alter und Geschlecht'], ['key' => 'competences', 'label' => 'Kompetenzen'], ['key' => 'experience', 'label' => 'Erfahrung'], ['key' => 'note', 'label' => 'Nachricht und Notiz'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'action', 'label' => 'Aktion']]">
            @foreach ($rows as $row)
                @php($application = $row['application'])
                <tr>
                    <td>
                        {{ $row['name'] }}
                        @if ($row['lead'])<br><x-vdbs.data.badge tone="info">möchte leiten</x-vdbs.data.badge>@endif
                        @if ($row['conflicts'])<br><x-vdbs.data.badge tone="warning">Überschneidung: {{ implode(', ', $row['conflicts']) }}</x-vdbs.data.badge>@endif
                    </td>
                    <td>{{ $row['age'] !== null ? $row['age'].' Jahre' : 'Alter unbekannt' }}@if ($canManage)<br>{{ $row['gender']?->label() ?? 'keine Angabe' }}@endif</td>
                    <td>
                        @forelse ($row['competences'] as $competence)
                            <x-vdbs.data.badge :tone="in_array($competence, $summary['wanted'], true) ? 'success' : 'neutral'">{{ $label($competence) }}</x-vdbs.data.badge>
                        @empty –
                        @endforelse
                    </td>
                    <td>
                        {{ $row['seminars_done'] }} Seminar(e) im Portal
                        @if ($row['lead_experience'])<br>Erfahrung in der Seminarleitung @endif
                        @if ($row['experience_note'])<br><small>{{ \Illuminate\Support\Str::limit($row['experience_note'], 200) }}</small>@endif
                    </td>
                    <td>
                        {{ $application->note ?? '–' }}
                        @if ($canManage)
                            <form method="post" action="{{ route('seminars.team.note', [$seminar, $application]) }}" style="margin-top:var(--space-2)">
                                @csrf @method('put')
                                <label class="vdbs-sr-only" for="note-{{ $application->id }}">Interne Notiz zu {{ $row['name'] }}</label>
                                <input class="vdbs-control vdbs-control--size-s" id="note-{{ $application->id }}" name="internal_note" maxlength="300" placeholder="Interne Notiz" value="{{ $application->internal_note }}">
                                <x-vdbs.actions.button type="submit" size="s" variant="tertiary">Notiz speichern</x-vdbs.actions.button>
                            </form>
                        @elseif ($application->internal_note)
                            <br><small>Notiz: {{ $application->internal_note }}</small>
                        @endif
                    </td>
                    <td><x-vdbs.data.badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-vdbs.data.badge></td>
                    <td>
                        @if ($canManage)
                            <div class="vdbs-btn-group">
                                @if ($application->status !== \App\Modules\Seminars\Enums\ApplicationStatus::Confirmed)
                                    <form method="post" action="{{ route('seminars.team.decide', [$seminar, $application, 'bestaetigen']) }}">@csrf<x-vdbs.actions.button type="submit" size="s" icon="check">Ins Team</x-vdbs.actions.button></form>
                                @endif
                                @if ($application->status !== \App\Modules\Seminars\Enums\ApplicationStatus::Declined)
                                    <form method="post" action="{{ route('seminars.team.decide', [$seminar, $application, 'ablehnen']) }}">@csrf<x-vdbs.actions.button type="submit" size="s" variant="secondary" shape="outline">Nicht berücksichtigen</x-vdbs.actions.button></form>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <aside aria-labelledby="team-summary-heading" style="margin-top:var(--space-5);padding:var(--space-4);background:var(--bg-surface)">
            <h3 id="team-summary-heading" style="margin-top:0;font-size:var(--font-size-h4)">Zusammensetzung des Teams</h3>
            @if ($summary['size'] === 0)
                <p>Noch niemand im Team.</p>
            @else
                <x-vdbs.data.detail-list layout="inline">
                    <x-vdbs.data.detail-item label="Größe">{{ $summary['size'] }} von {{ $needed }}</x-vdbs.data.detail-item>
                    @if ($canManage)<x-vdbs.data.detail-item label="Geschlechter">@foreach ($summary['genders'] as $gender => $count){{ $count }} {{ $gender }}{{ ! $loop->last ? ', ' : '' }}@endforeach</x-vdbs.data.detail-item>@endif
                    <x-vdbs.data.detail-item label="Durchschnittsalter">{{ $summary['average_age'] !== null ? $summary['average_age'].' Jahre' : 'unbekannt' }}</x-vdbs.data.detail-item>
                    <x-vdbs.data.detail-item label="Seminarleitung">{{ $summary['leaders'] }} mit Interesse oder Erfahrung</x-vdbs.data.detail-item>
                    @if ($summary['wanted'])
                        <x-vdbs.data.detail-item label="Gesuchte Kompetenzen">
                            @foreach ($summary['covered'] as $key)<x-vdbs.data.badge tone="success">{{ $label($key) }}: abgedeckt</x-vdbs.data.badge> @endforeach
                            @foreach ($summary['missing'] as $key)<x-vdbs.data.badge tone="warning">{{ $label($key) }}: fehlt</x-vdbs.data.badge> @endforeach
                        </x-vdbs.data.detail-item>
                    @endif
                </x-vdbs.data.detail-list>
            @endif
        </aside>
    @endif
</section>
