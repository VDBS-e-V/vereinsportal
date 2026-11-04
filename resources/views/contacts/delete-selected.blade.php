@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Teamkontakte', 'href' => route('contacts.index')], ['label' => 'Auswahl löschen']];
@endphp

<x-layouts.app title="Auswahl löschen" width="medium">
    <x-vdbs.layout.page-header title="{{ $contacts->count() === 1 ? '1 Kontakt' : $contacts->count().' Kontakte' }} löschen?" lead="Die Adressen und ihre persönlichen Zugänge werden endgültig gelöscht. Beim nächsten Import können sie wieder auftauchen." :breadcrumb="$crumbs" />

    <x-vdbs.data.table caption="Zu löschende Kontakte" :columns="[['key' => 'email', 'label' => 'E-Mail-Adresse'], ['key' => 'name', 'label' => 'Name']]">
        @foreach ($contacts as $row)
            <tr><td>{{ $row->email }}</td><td>{{ $row->first_name || $row->last_name ? $row->displayName() : '–' }}</td></tr>
        @endforeach
    </x-vdbs.data.table>

    <x-vdbs.forms.form :action="route('contacts.delete-selected')" style="margin-top:var(--space-6)">
        <input type="hidden" name="confirmed" value="1">
        @foreach ($contacts as $row)
            <input type="hidden" name="ids[]" value="{{ $row->id }}">
        @endforeach
        <x-slot:actions>
            <x-vdbs.actions.button type="submit" variant="danger" icon="trash-2">Endgültig löschen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('contacts.index')">Abbrechen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
