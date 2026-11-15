@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Konto', 'href' => route('account.home')], ['label' => 'Meine Tickets']];
@endphp

<x-layouts.app title="Meine Tickets" width="wide">
    <x-vdbs.layout.page-header title="Meine Tickets" lead="Ihre Anmeldungen zu Veranstaltungen. Den Ticketcode brauchen Sie beim Einlass und für die Teilnahmebestätigung." :breadcrumb="$crumbs" />

    @if ($registrations->isEmpty())
        <x-vdbs.feedback.empty-state title="Noch keine Tickets" icon="ticket">
            <p>Zu Ihrer E-Mail-Adresse gibt es noch keine Anmeldung. Anmelden können Sie sich bei den <a href="{{ route('events.public.index') }}">Veranstaltungen</a>.</p>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Meine Tickets" :columns="[['key' => 'event', 'label' => 'Veranstaltung'], ['key' => 'dates', 'label' => 'Termine'], ['key' => 'ticket', 'label' => 'Ticketcode'], ['key' => 'status', 'label' => 'Stand']]">
            @foreach ($registrations as $registration)
                <tr>
                    <td><a href="{{ route('events.public.show', $registration->event->slug) }}">{{ $registration->event->title }}</a>@if (filled($registration->event->location))<br><span class="vdbs-hint">{{ $registration->event->location }}</span>@endif</td>
                    <td>@forelse ($registration->event->sessions as $session){{ $session->starts_at->format('d.m.Y, H:i') }} Uhr<br>@empty – @endforelse</td>
                    <td>@if ($registration->isConfirmed() && ! $registration->isWaitlisted())<code>{{ $registration->ticket() }}</code>@else – @endif</td>
                    <td>
                        @if ($registration->isWaitlisted())
                            <x-vdbs.data.badge tone="warning">Warteliste</x-vdbs.data.badge>
                        @elseif ($registration->isConfirmed())
                            <x-vdbs.data.badge tone="success">bestätigt</x-vdbs.data.badge>
                        @else
                            <x-vdbs.data.badge tone="neutral">E-Mail noch nicht bestätigt</x-vdbs.data.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
    @endif
</x-layouts.app>
