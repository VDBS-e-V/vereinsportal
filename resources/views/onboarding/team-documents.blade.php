@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Koordination'], ['label' => 'Teamende', 'href' => route('team-onboarding.index')], ['label' => 'Dokumente']];
@endphp

<x-layouts.app title="Dokumente der Teamenden" width="wide">
    <x-vdbs.layout.page-header title="Dokumente der Teamenden" lead="Diese Dokumente lesen die Teamenden bei ihrer Einarbeitung und bestätigen sie. Mit einer neuen Fassung bestätigen neue Teamende die neue." :breadcrumb="$crumbs">
        <x-slot:actions><x-vdbs.actions.button variant="secondary" :href="route('team-onboarding.index')" icon="users">Zur Übersicht</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.layout.page-header>

    <x-vdbs.forms.error-summary />

    @include('onboarding._documents', ['kinds' => $kinds, 'documents' => $documents, 'canManage' => $canManage, 'uploadRoute' => route('team-onboarding.documents.upload')])
</x-layouts.app>
