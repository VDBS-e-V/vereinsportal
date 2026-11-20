@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Schnellzugriffe']];
@endphp

<x-layouts.app title="Schnellzugriffe" width="wide">
    <x-vdbs.layout.page-header title="Schnellzugriffe im Profilmenü" lead="Je Rolle bis zu drei wichtige Links. Im Profilmenü erscheinen die wichtigsten Links aller Rollen einer Person (höchstens vier, doppelte Adressen nur einmal). Eine kleine Zahl bei „Wichtigkeit“ heißt wichtiger (1 = am wichtigsten)." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('role-links.update')" method="put">
        @foreach ($roles as $key => $label)
            <x-vdbs.forms.form-section :title="$label">
                @for ($i = 0; $i < $perRole; $i++)
                    @php $link = ($links[$key] ?? collect())->values()->get($i); @endphp
                    <x-vdbs.forms.form-grid :columns="3">
                        <x-vdbs.forms.input :id="'l-'.$key.'-'.$i.'-label'" :name="'links['.$key.']['.$i.'][label]'" label="Titel" :optional="true" maxlength="60" :value="$link?->label" />
                        <x-vdbs.forms.input :id="'l-'.$key.'-'.$i.'-url'" :name="'links['.$key.']['.$i.'][url]'" label="Adresse" :optional="true" maxlength="255" placeholder="/mitglieder oder https://…" :value="$link?->url" />
                        <x-vdbs.forms.input :id="'l-'.$key.'-'.$i.'-priority'" :name="'links['.$key.']['.$i.'][priority]'" label="Wichtigkeit" type="number" :optional="true" :value="$link?->priority ?? $i + 1" hint="1 = am wichtigsten" />
                    </x-vdbs.forms.form-grid>
                @endfor
            </x-vdbs.forms.form-section>
        @endforeach
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
