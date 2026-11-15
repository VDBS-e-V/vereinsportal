@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Kontaktformular', 'href' => route('contact-admin.topics.index')], ['label' => 'Formulare']];
@endphp

<x-layouts.app title="Kontaktformulare" width="wide">
    <x-vdbs.layout.page-header title="Formulare" lead="Die Formulare hinter den Themen: wer die Eingaben erhält, ob eine Nachricht oder nur Angaben abgefragt werden und welche eigenen Felder es gibt." :breadcrumb="$crumbs">
        <x-slot:actions>
            <x-vdbs.actions.button :href="route('contact-admin.forms.create')" icon="plus">Neues Formular</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.layout.page-header>

    @include('contact-admin._nav', ['active' => 'forms'])

    <form method="post" action="{{ route('contact-admin.forms.destroy-many') }}" id="bulk-contact-forms">
        @csrf
        <x-vdbs.data.table caption="Kontaktformulare" :columns="[['key' => 'sel', 'label' => 'Auswahl', 'selectAll' => 'bulk-contact-forms'], ['key' => 'name', 'label' => 'Name'], ['key' => 'kind', 'label' => 'Art'], ['key' => 'to', 'label' => 'Empfänger'], ['key' => 'topics', 'label' => 'Themen'], ['key' => 'status', 'label' => 'Status']]">
            @foreach ($forms as $form)
                <tr>
                    <x-vdbs.data.table-check name="ids[]" :value="$form->id" :label="$form->name.' auswählen'" form="bulk-contact-forms" :disabled="$form->is_default" />
                    <td>
                        <a href="{{ route('contact-admin.forms.edit', $form) }}"><strong>{{ $form->name }}</strong></a>
                        @if ($form->is_default) <x-vdbs.data.badge tone="info">Standard</x-vdbs.data.badge>@endif
                        @if (filled($form->description))<br><small>{{ $form->description }}</small>@endif
                    </td>
                    <td>{{ $form->isMessage() ? 'Nachricht' : 'Nur Angaben' }}@if (count($form->customFields()) > 0) <br><small>{{ count($form->customFields()) }} eigene Felder</small>@endif</td>
                    <td>{{ $form->recipient_label }}<br><small>{{ $form->recipient_email }}</small></td>
                    <td>{{ $form->topics_count }}</td>
                    <td>
                        @if ($form->is_active)
                            <x-vdbs.data.badge tone="success">aktiv</x-vdbs.data.badge>
                        @else
                            <x-vdbs.data.badge tone="neutral">ausgeschaltet</x-vdbs.data.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-vdbs.data.table>
        <x-vdbs.actions.button type="submit" variant="danger-text" size="s" onclick="return confirm('Die ausgewählten Formulare löschen? Themen, die dorthin führen, sind danach verborgen.')">Ausgewählte löschen</x-vdbs.actions.button>
    </form>
</x-layouts.app>
