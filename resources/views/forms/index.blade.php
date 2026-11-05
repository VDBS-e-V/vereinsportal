@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Anträge']])
<x-layouts.app title="Meine Anträge" width="wide">
    <x-vdbs.layout.page-header title="Meine Anträge" lead="Hier stellen Sie Anträge an die Geschäftsstelle und sehen, wie weit sie sind." :breadcrumb="$crumbs" />

    @error('form') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror

    <section aria-labelledby="new-heading">
        <h2 id="new-heading" style="font-size:var(--font-size-h3)">Neuer Antrag</h2>
        <div style="display:flex;flex-wrap:wrap;gap:var(--space-4)">
            @foreach ($types as $key => $type)
                <form method="post" action="{{ route('forms.store') }}">@csrf
                    <input type="hidden" name="type" value="{{ $key }}">
                    <x-vdbs.actions.button type="submit" icon="plus">{{ $type['label'] }}</x-vdbs.actions.button>
                    <p class="vdbs-hint" style="max-width:22rem">{{ $type['hint'] }}</p>
                </form>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="mine-heading" style="margin-top:var(--space-8)">
        <h2 id="mine-heading" style="font-size:var(--font-size-h3)">Meine bisherigen Anträge</h2>
        @if ($applications->isEmpty())
            <x-vdbs.feedback.empty-state title="Noch kein Antrag" icon="info"><p>Wählen Sie oben die Art des Antrags.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Meine Anträge" :columns="[['key' => 'number', 'label' => 'Nummer'], ['key' => 'type', 'label' => 'Art'], ['key' => 'purpose', 'label' => 'Zweck'], ['key' => 'total', 'label' => 'Summe'], ['key' => 'status', 'label' => 'Stand']]">
                @foreach ($applications as $application)
                    <tr>
                        <td style="white-space:nowrap"><a href="{{ $application->isEditable() ? route('forms.edit', $application) : route('forms.show', $application) }}">{{ $application->number }}</a></td>
                        <td>{{ $application->typeLabel() }}</td>
                        <td>{{ $application->purpose ?: '–' }}</td>
                        <td style="white-space:nowrap">{{ $application->euro() }}</td>
                        <td><x-vdbs.data.badge :tone="in_array($application->status, ['approved', 'paid'], true) ? 'success' : ($application->status === 'rejected' ? 'error' : ($application->status === 'returned' ? 'warning' : 'neutral'))">{{ $application->statusLabel() }}</x-vdbs.data.badge></td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
        @endif
    </section>
</x-layouts.app>
