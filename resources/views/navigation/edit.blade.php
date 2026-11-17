@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Navigation']];
@endphp

<x-layouts.app title="Navigation" width="wide">
    <x-vdbs.layout.page-header title="Navigation" lead="Legen Sie fest, welche Bereiche und Seiten im Kopfmenü und auf den Startseiten der Bereiche erscheinen. So zeigt das Portal nur bestätigte Funktionen. Das Ausblenden betrifft nur die Menüs: Wer eine Adresse kennt und die Rolle dafür hat, erreicht die Seite weiterhin." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('navigation.update')" method="put">
        @foreach ($areas as $entry)
            <x-vdbs.forms.form-section :title="$entry['area']->label()">
                <x-vdbs.forms.checkbox :id="'area-'.$entry['area']->value" :name="'areas['.$entry['area']->value.']'" label="Bereich im Kopfmenü anzeigen" :checked="$entry['enabled']" hint="Ist der Bereich aus, sehen ihn auch Personen mit der passenden Rolle nicht im Menü." />
                @foreach ($entry['pages'] as $page)
                    <x-vdbs.forms.checkbox :id="'page-'.$page['token']" :name="'pages['.$page['token'].']'" :label="($page['group'] ? $page['group'].' › ' : '').$page['label']" :checked="$page['enabled']" />
                @endforeach
            </x-vdbs.forms.form-section>
        @endforeach
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
