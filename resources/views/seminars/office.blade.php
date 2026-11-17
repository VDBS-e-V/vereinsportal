@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Seminare']];
    $basis = ['contract' => 'Vertrag', 'commitment' => 'Zusage'];
@endphp

<x-layouts.app title="Seminare der Geschäftsstelle" width="full">
    <x-vdbs.layout.container>
        <x-vdbs.layout.page-header title="Seminare" lead="Alle Seminare mit Stand von KVA, Freigabe und Vertrag. Von hier prüfen, senden und freigeben Sie Anfragen und erzeugen den Vertrag mit der Schule." :breadcrumb="$crumbs" />

        <form method="get" action="{{ route('seminar-office.index') }}" class="vdbs-task-filter" aria-label="Seminare suchen und filtern">
            <x-vdbs.forms.input name="q" label="Suchen" type="search" :optional="true" maxlength="100" :value="$filters['q']" />
            <x-vdbs.forms.select name="status" label="Status" :optional="true" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
            <x-vdbs.forms.select name="area" label="Koordinationsgebiet" :optional="true" placeholder="Alle" :options="$areas" :value="$filters['area']" />
            <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Filtern</x-vdbs.actions.button>
        </form>

        @if ($seminars->isEmpty())
            <x-vdbs.feedback.empty-state title="Keine Seminare" icon="calendar"><p>Zu diesem Filter gibt es keine Seminare.</p></x-vdbs.feedback.empty-state>
        @else
            <x-vdbs.data.table caption="Seminare" :columns="[['key' => 'nr', 'label' => 'Nummer'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'title', 'label' => 'Seminar'], ['key' => 'status', 'label' => 'Status'], ['key' => 'quote', 'label' => 'KVA'], ['key' => 'release', 'label' => 'Freigabe'], ['key' => 'act', 'label' => 'Aktionen']]">
                @foreach ($seminars as $seminar)
                    @php $approved = $seminar->quotes_count > 0 && $workflow->quoteApproved($seminar); @endphp
                    <tr>
                        <td>{{ $seminar->number }}</td>
                        <td>{{ $seminar->partner->name }}@if ($seminar->partner->coordination_area)<br><span class="vdbs-hint">{{ $seminar->partner->coordination_area }}</span>@endif</td>
                        <td><a href="{{ route('seminars.show', $seminar) }}">{{ $seminar->title }}</a></td>
                        <td><x-vdbs.data.badge :tone="$seminar->status->tone()">{{ $seminar->status->label() }}</x-vdbs.data.badge></td>
                        <td>{{ $seminar->quotes_count === 0 ? 'kein KVA' : ($approved ? 'freigegeben' : 'noch nicht freigegeben') }}</td>
                        <td>{{ $seminar->release_basis ? $basis[$seminar->release_basis] ?? '' : '–' }}@if ($seminar->school_contract_signed_at)<br><span class="vdbs-hint">unterschrieben {{ $seminar->school_contract_signed_at->format('d.m.Y') }}</span>@endif</td>
                        <td>
                            <x-vdbs.actions.button :href="route('seminars.show', $seminar)" variant="secondary" size="s" icon="eye">Öffnen</x-vdbs.actions.button>
                            <x-vdbs.actions.button :href="route('school-contracts.show', $seminar)" variant="secondary" size="s" icon="file-text">Schulvertrag</x-vdbs.actions.button>
                        </td>
                    </tr>
                @endforeach
            </x-vdbs.data.table>
            {{ $seminars->links() }}
        @endif
        <p><a href="{{ route('school-contracts.template') }}">Vorlage für den Vertrag mit der Schule bearbeiten</a></p>
    </x-vdbs.layout.container>
</x-layouts.app>
