@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Seminare']];
@endphp

<x-layouts.app title="Seminare" width="wide">
    <x-vdbs.layout.page-header title="Ausgeschriebene Seminare" lead="Melden Sie Ihr Interesse und teilen Sie mit, wann Sie können. Die Koordination wählt das Team aus und meldet sich bei Ihnen." :breadcrumb="$crumbs" />

    @error('apply')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

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
