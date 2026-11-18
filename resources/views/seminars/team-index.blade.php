@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Seminare']];
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Ausgeschriebene Seminare" lead="Melden Sie Ihr Interesse und teilen Sie mit, wann Sie können. Die Koordination wählt das Team aus und meldet sich bei Ihnen." :breadcrumb="$crumbs" />

    @error('apply')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    @if ($mine->isNotEmpty())
        <h2 style="font-size:var(--font-size-h3)">Ihre Seminare</h2>
        <x-vdbs.data.table caption="Ihre Seminare" :columns="[['key' => 'title', 'label' => 'Seminar'], ['key' => 'date', 'label' => 'Erster Termin'], ['key' => 'outcome', 'label' => 'Ergebnis']]">
            @foreach ($mine as $application)
                <tr>
                    <td>{{ $application->seminar->title }} · {{ $application->seminar->partner->name }}@if ($application->outcome === 'confirmed') · <a href="{{ route('seminars.files.planner', $application->seminar) }}">Druckplaner</a> · <a href="{{ route('seminars.files.folder', $application->seminar) }}">Mappe (ZIP)</a>@endif</td>
                    <td>{{ $application->seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</td>
                    <td>
                        @if ($application->outcome === 'confirmed')
                            <x-vdbs.data.badge tone="success">Zusage{{ $application->seminar->lead_user_id === $application->user_id ? ' (Seminarleitung)' : '' }}</x-vdbs.data.badge>
                        @elseif ($application->outcome === 'waitlist')
                            <x-vdbs.data.badge tone="warning">Warteliste, Platz {{ $application->waitlist_position }}</x-vdbs.data.badge>
                        @elseif ($application->outcome === 'dropped')
                            <x-vdbs.data.badge tone="neutral">Ausgeschieden</x-vdbs.data.badge>
                        @else
                            <x-vdbs.data.badge tone="neutral">Absage</x-vdbs.data.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Ausgeschrieben</h2>
    @endif

    @forelse ($seminars as $seminar)
        @php $application = $applications->get($seminar->id); $applied = $application?->status === 'applied'; @endphp
        <section aria-labelledby="sem-{{ $seminar->id }}" style="margin-bottom:var(--space-8)">
            <h2 id="sem-{{ $seminar->id }}" style="font-size:var(--font-size-h3)">{{ $seminar->title }} <x-vdbs.data.badge :tone="$applied ? 'success' : 'neutral'">{{ $applied ? 'Interesse gemeldet' : 'offen' }}</x-vdbs.data.badge></h2>
            <p>{{ $seminar->partner->name }}@if (filled($seminar->format)) · {{ $seminar->format }}@endif · Bewerbung bis {{ $seminar->application_deadline?->format('d.m.Y') }}</p>
            <ul>
                @foreach ($seminar->sessions as $session)
                    <li>{{ $session->starts_at->format('d.m.Y, H:i') }} Uhr{{ filled($session->location) ? ', '.$session->location : '' }}</li>
                @endforeach
            </ul>

            <x-vdbs.forms.form :action="route('seminars.team.apply', $seminar)" :summary="false">
                <x-vdbs.forms.textarea :id="'avail-'.$seminar->id" name="availability" label="Wann können Sie? (zum Beispiel einzelne Tage)" :optional="true" maxlength="1000" :value="$application?->availability" />
                <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">{{ $applied ? 'Angaben ändern' : 'Interesse melden' }}</x-vdbs.actions.button></x-slot:actions>
            </x-vdbs.forms.form>

            @if ($applied)
                <form method="post" action="{{ route('seminars.team.withdraw', $seminar) }}">@csrf @method('delete')
                    <x-vdbs.actions.button type="submit" size="s" variant="danger-text">Meldung zurücknehmen</x-vdbs.actions.button>
                </form>
            @endif
        </section>
    @empty
        <x-vdbs.feedback.empty-state title="Zurzeit nichts ausgeschrieben" icon="calendar"><p>Sobald ein Seminar für Teamende ausgeschrieben wird, sehen Sie es hier.</p></x-vdbs.feedback.empty-state>
    @endforelse
</x-layouts.app>
