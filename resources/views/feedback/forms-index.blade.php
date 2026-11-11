@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Feedbackformulare']])
<x-layouts.app title="Feedbackformulare" width="wide">
    <x-vdbs.layout.page-header title="Feedbackformulare" lead="Je ein Fragebogen für Schülerinnen und Schüler, Lehrkräfte und Teamende. Änderungen gelten für Links, die danach angelegt werden; bestehende Links behalten ihre Fassung." :breadcrumb="$crumbs" />

    <x-vdbs.data.table caption="Feedbackformulare" :columns="[['key' => 'audience', 'label' => 'Gruppe'], ['key' => 'title', 'label' => 'Titel'], ['key' => 'q', 'label' => 'Fragen'], ['key' => 'v', 'label' => 'Fassung'], ['key' => 'upd', 'label' => 'Geändert'], ['key' => 'act', 'label' => 'Aktionen']]">
        @foreach ($forms as $form)
            <tr>
                <td>{{ $form->audienceLabel() }}</td>
                <td>{{ $form->title }}</td>
                <td>{{ count($form->questions) }}</td>
                <td>{{ $form->version }}</td>
                <td>{{ $form->updated_at?->format('d.m.Y') }}</td>
                <td><a href="{{ route('feedback-forms.edit', $form) }}">Bearbeiten</a> · <a href="{{ route('feedback-forms.preview', $form) }}">Vorschau</a></td>
            </tr>
        @endforeach
    </x-vdbs.data.table>
</x-layouts.app>
