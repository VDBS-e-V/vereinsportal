@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Teamende']];
    $name = fn ($user) => $user->person?->fullName() ?: $user->email;
@endphp

<x-layouts.app title="Teamende" width="wide">
    <x-vdbs.layout.page-header title="Teamende" lead="Einarbeitung der Teamenden: Sie beginnt mit der Rolle Teamende und endet, wenn alle Schritte erledigt sind." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button variant="secondary" :href="route('team-onboarding.documents')" icon="clipboard-list">Dokumente der Teamenden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="3" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Teamende" value="{{ $stats['total'] }}" icon="users" />
        <x-vdbs.data.stat label="In Einarbeitung" value="{{ $stats['open'] }}" icon="clock" :tone="$stats['open'] > 0 ? 'warning' : 'neutral'" />
        <x-vdbs.data.stat label="Einarbeitung abgeschlossen" value="{{ $stats['done'] }}" icon="check" tone="success" />
    </x-vdbs.layout.grid>

    @if ($onboardings->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Teamenden" icon="users">
            <p>Teamende erscheinen hier, sobald ein Konto die Rolle Teamende erhält. Die Rolle vergeben Sie unter Nutzer.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Einarbeitung der Teamenden" :columns="[['key' => 'name', 'label' => 'Teamende:r'], ['key' => 'since', 'label' => 'Seit'], ['key' => 'progress', 'label' => 'Schritte'], ['key' => 'status', 'label' => 'Stand']]">
            @foreach ($onboardings as $row)
                @php [$done, $all] = $row->progress(); @endphp
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('team-onboarding.show', $row->user) }}">{{ $name($row->user) }}</a></td>
                    <td>{{ $row->started_at->format('d.m.Y') }}</td>
                    <td>{{ $done }} von {{ $all }}</td>
                    <td>@if ($row->isComplete())<x-vdbs.data.badge tone="success">Abgeschlossen</x-vdbs.data.badge>@else<x-vdbs.data.badge tone="warning">In Einarbeitung</x-vdbs.data.badge>@endif</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
