@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Nutzer']];
    $total = $users->total();
    $count = $total === 1 ? '1 Nutzer' : $total.' Nutzer';
@endphp

<x-layouts.app title="Nutzer" width="wide">
    <x-vdbs.layout.page-header title="Nutzer" lead="Konten, Rollen und Zugänge der Mitglieder." :breadcrumb="$crumbs">
        @if ($canManage)
            <x-slot:actions>
                <x-vdbs.actions.button :href="route('admin.users.create')" icon="plus">Nutzer anlegen</x-vdbs.actions.button>
            </x-slot:actions>
        @endif
    </x-vdbs.layout.page-header>

    <x-vdbs.layout.grid :columns="4" style="margin-bottom:var(--space-6)">
        <x-vdbs.data.stat label="Aktive Konten" value="{{ $stats['active'] }}" icon="users" tone="success" />
        <x-vdbs.data.stat label="Eingeladen" value="{{ $stats['invited'] }}" icon="mail" hint="Noch kein Passwort festgelegt" />
        <x-vdbs.data.stat label="Ohne Zwei-Faktor" value="{{ $stats['withoutTwoFactor'] }}" icon="shield" :tone="$stats['withoutTwoFactor'] > 0 ? 'warning' : 'success'" hint="Aktive Konten" />
        <x-vdbs.data.stat label="Gesperrt" value="{{ $stats['disabled'] }}" icon="lock" :tone="$stats['disabled'] > 0 ? 'error' : 'neutral'" />
    </x-vdbs.layout.grid>

    <x-vdbs.data.toolbar label="Nutzer filtern" :count="$count" :active="$active" :reset-href="route('admin.users.index')" search-placeholder="Name oder E-Mail suchen">
        <x-vdbs.forms.select name="status" label="Status" placeholder="Alle" :options="$statusOptions" :value="$filters['status']" />
        <x-vdbs.forms.select name="role" label="Rolle" placeholder="Alle" :options="$roleOptions" :value="$filters['role']" />
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </x-vdbs.data.toolbar>

    @if ($users->isEmpty())
        <x-vdbs.feedback.empty-state title="Keine Nutzer gefunden" icon="search">
            <p>Zu den gewählten Filtern gibt es keine Konten. Prüfen Sie die Schreibweise oder entfernen Sie Filter.</p>
            <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('admin.users.index')">Filter zurücksetzen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.feedback.empty-state>
    @else
        <x-vdbs.data.table caption="Nutzerkonten" :columns="$columns" :sort="$sort" :direction="$direction">
            @foreach ($users as $row)
                <tr class="vdbs-table__row--link">
                    <td><a class="vdbs-table__row-link" href="{{ route('admin.users.show', $row) }}">{{ $row->person?->fullName() ?: $row->email }}</a></td>
                    <td>{{ $row->email }}</td>
                    <td><x-vdbs.data.badge :tone="$row->status->tone()">{{ $row->status->label() }}</x-vdbs.data.badge></td>
                    <td>
                        @forelse ($row->activeRoleAssignments as $assignment)
                            <x-vdbs.data.badge>{{ $assignment->role?->name }}</x-vdbs.data.badge>
                        @empty
                            <span aria-hidden="true">–</span><span class="vdbs-sr-only">keine Rolle</span>
                        @endforelse
                    </td>
                    <td>{{ $row->last_login_at?->format('d.m.Y, H:i') ?? '–' }}</td>
                </tr>
            @endforeach
        </x-vdbs.data.table>

        <div style="margin-top:var(--space-4)"><x-vdbs.navigation.pagination :paginator="$users" /></div>
    @endif
</x-layouts.app>
