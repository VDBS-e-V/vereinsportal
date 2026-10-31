@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Veranstaltungen', 'href' => route('events.admin.index')], ['label' => $event->title, 'href' => route('events.admin.show', $event)], ['label' => 'Anwesenheit']];
@endphp

<x-layouts.app title="Anwesenheit" width="wide">
    <x-vdbs.layout.page-header :title="'Anwesenheit: '.$event->title" :lead="$session->period().' · '.$presentCount.' anwesend'" :breadcrumb="$crumbs" />

    @if (session('ticket_unknown'))
        <x-vdbs.feedback.alert variant="error">{{ session('ticket_unknown') }}</x-vdbs.feedback.alert>
    @endif
    <x-vdbs.forms.error-summary />

    @if ($canManage)
        <x-vdbs.forms.form :action="route('events.admin.attendance.ticket', [$event, $session])" :summary="false">
            <x-vdbs.forms.input name="ticket" label="Ticketcode eingeben" :required="true" maxlength="20" autocomplete="off" hint="Trägt die Person sofort als anwesend ein" autofocus />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Eintragen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endif

    <form method="get" role="search" style="margin:var(--space-5) 0">
        <x-vdbs.forms.input name="q" label="Suchen (Name, Schule, Klasse)" :value="$search" :optional="true" />
        <x-vdbs.actions.button type="submit" variant="secondary" icon="search">Suchen</x-vdbs.actions.button>
    </form>

    @if ($registrations->isEmpty())
        <p>Keine bestätigten Anmeldungen gefunden.</p>
    @else
        <x-vdbs.data.table caption="Anwesenheitsliste" :columns="[['key' => 'name', 'label' => 'Name'], ['key' => 'school', 'label' => 'Schule'], ['key' => 'class', 'label' => 'Klasse'], ['key' => 'present', 'label' => 'Anwesend']]">
            @foreach ($registrations as $row)
                @php($isPresent = $present->has($row->id))
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->school }}</td>
                    <td>{{ $row->class_name }}</td>
                    <td>
                        @if ($canManage)
                            <form method="post" action="{{ route('events.admin.attendance.mark', [$event, $session]) }}">
                                @csrf
                                <input type="hidden" name="registration" value="{{ $row->id }}">
                                <input type="hidden" name="present" value="{{ $isPresent ? 0 : 1 }}">
                                <input type="hidden" name="q" value="{{ $search }}">
                                <x-vdbs.actions.button type="submit" size="s" :variant="$isPresent ? 'primary' : 'secondary'" :shape="$isPresent ? 'solid' : 'outline'" :icon="$isPresent ? 'check' : null">{{ $isPresent ? 'Anwesend' : 'Als anwesend eintragen' }}</x-vdbs.actions.button>
                            </form>
                        @else
                            {{ $isPresent ? 'Ja' : '–' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
