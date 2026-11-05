@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Anträge', 'href' => route('forms-office.index')], ['label' => 'Kostenstellen']])
<x-layouts.app title="Kostenstellen" width="medium">
    <x-vdbs.layout.page-header title="Kostenstellen" lead="Zuordnung von Anträgen. Beendete Kostenstellen stehen nicht mehr zur Auswahl, alte Anträge behalten sie." :breadcrumb="$crumbs" />

    <x-vdbs.data.table caption="Kostenstellen" :columns="[['key' => 'code', 'label' => 'Kennung'], ['key' => 'name', 'label' => 'Name'], ['key' => 'state', 'label' => 'Stand'], ['key' => 'act', 'label' => 'Aktion']]">
        @foreach ($centers as $center)
            <tr>
                <td>{{ $center->code }}</td><td>{{ $center->name }}</td>
                <td>{{ $center->active ? 'aktiv' : 'beendet' }}</td>
                <td>@can('forms.manage')<form method="post" action="{{ route('forms-office.cost-centers.toggle', $center) }}">@csrf<x-vdbs.actions.button type="submit" variant="secondary">{{ $center->active ? 'Beenden' : 'Wieder aktivieren' }}</x-vdbs.actions.button></form>@endcan</td>
            </tr>
        @endforeach
    </x-vdbs.data.table>

    @can('forms.manage')
        <x-vdbs.forms.form :action="route('forms-office.cost-centers.store')" style="margin-top:var(--space-6)">
            <x-vdbs.forms.form-section title="Neue Kostenstelle">
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input name="code" label="Kennung" :required="true" maxlength="40" hint="Buchstaben, Ziffern, Punkt, Bindestrich" />
                    <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="150" />
                </x-vdbs.forms.form-grid>
            </x-vdbs.forms.form-section>
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Anlegen</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
    @endcan
</x-layouts.app>
