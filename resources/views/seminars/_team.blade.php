@php
    $needed = (int) $seminar->team_needed;
    $name = fn ($application) => $application->user->person?->fullName() ?: $application->user->email;
@endphp

<section aria-labelledby="team-heading" style="margin-top:var(--space-8)">
    <h2 id="team-heading" style="font-size:var(--font-size-h3)">Teamende</h2>

    <p>
        @if ($needed > 0)
            Bedarf: <strong>{{ $needed }}</strong>, bestätigt: <strong>{{ $teamConfirmed }}</strong>.
            @if ($teamOpen)
                Teamende können sich bewerben{{ $teamConfirmed >= $needed ? ' (der Bedarf ist gedeckt, die Seminare erscheinen nicht mehr in der Liste)' : '' }}.
            @else
                Bewerbungen sind erst im Status „Terminiert“ möglich.
            @endif
            @if ($teamConfirmed >= $needed && $seminar->status === \App\Modules\Seminars\Enums\SeminarStatus::Scheduled)
                Der Bedarf ist gedeckt: Sie können das Seminar jetzt bestätigen.
            @endif
        @else
            Noch kein Bedarf eingetragen. Mit einem Bedarf und dem Status „Terminiert“ erscheint das Seminar bei den Teamenden.
        @endif
    </p>

    @if ($canManage)
        <x-vdbs.forms.form :action="route('seminars.team.need', $seminar)" method="put" :summary="false">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.input name="team_needed" label="Bedarf an Teamenden" type="number" min="0" max="20" :optional="true" :value="$seminar->team_needed" hint="0 oder leer = keine Bewerbungen" />
            </x-vdbs.forms.form-grid>
            <x-slot:actions><x-vdbs.actions.button type="submit" variant="secondary" icon="save">Bedarf speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    @php($rows = $seminar->applications->where('status', '!=', \App\Modules\Seminars\Enums\ApplicationStatus::Withdrawn))
    @if ($rows->isNotEmpty())
        <x-vdbs.data.table caption="Bewerbungen auf das Seminar" :columns="[['key' => 'person', 'label' => 'Person'], ['key' => 'note', 'label' => 'Nachricht'], ['key' => 'status', 'label' => 'Stand'], ['key' => 'action', 'label' => 'Aktion']]">
            @foreach ($rows as $application)
                <tr>
                    <td>
                        {{ $name($application) }}
                        @if (! empty($conflicts[$application->id]))
                            <br><x-vdbs.data.badge tone="warning">Überschneidung: {{ implode(', ', $conflicts[$application->id]) }}</x-vdbs.data.badge>
                        @endif
                    </td>
                    <td>{{ $application->note ?? '–' }}</td>
                    <td><x-vdbs.data.badge :tone="$application->status->tone()">{{ $application->status->label() }}</x-vdbs.data.badge></td>
                    <td>
                        @if ($canManage)
                            <div class="vdbs-btn-group">
                                @if ($application->status !== \App\Modules\Seminars\Enums\ApplicationStatus::Confirmed)
                                    <form method="post" action="{{ route('seminars.team.decide', [$seminar, $application, 'bestaetigen']) }}">@csrf<x-vdbs.actions.button type="submit" size="s" icon="check">Bestätigen</x-vdbs.actions.button></form>
                                @endif
                                @if ($application->status !== \App\Modules\Seminars\Enums\ApplicationStatus::Declined)
                                    <form method="post" action="{{ route('seminars.team.decide', [$seminar, $application, 'ablehnen']) }}">@csrf<x-vdbs.actions.button type="submit" size="s" variant="secondary" shape="outline">Ablehnen</x-vdbs.actions.button></form>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</section>
