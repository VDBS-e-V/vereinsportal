@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Navigation', 'href' => route('navigation.headers')], ['label' => $area->label()]];
@endphp

<x-layouts.app title="Header bearbeiten" width="wide">
    <x-vdbs.layout.page-header :title="'Header bearbeiten „'.$area->label().'“'" lead="Link-Elemente des Bereichs. Titel leer lassen für den Standard. Eine kleine Zahl bei „Reihenfolge“ steht weiter vorn. Die Übersicht und Gruppen lassen sich nicht ausblenden. Eigene Links beginnen mit / (Seite im Portal) oder https://." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('navigation.header.update', $area->value)" method="put">
        <x-vdbs.forms.form-section title="Link-Elemente">
            @foreach ($rows as $row)
                <x-vdbs.forms.form-grid :columns="3">
                    <x-vdbs.forms.input :id="'i-'.$row['token'].'-label'" :name="'items['.$row['token'].'][label]'" :label="$row['title']" :optional="true" maxlength="60" :value="$row['label']" hint="Eigener Titel" />
                    <x-vdbs.forms.input :id="'i-'.$row['token'].'-sort'" :name="'items['.$row['token'].'][sort]'" label="Reihenfolge" type="number" :optional="true" :value="$row['sort']" />
                    @if ($row['pinned'])
                        <p class="vdbs-hint">Immer sichtbar.</p>
                    @else
                        <x-vdbs.forms.checkbox :id="'i-'.$row['token'].'-visible'" :name="'items['.$row['token'].'][visible]'" label="Anzeigen" :checked="$row['visible']" />
                    @endif
                </x-vdbs.forms.form-grid>
            @endforeach
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Eigene Links">
            @for ($i = 0; $i < $customSlots; $i++)
                @php $entry = $custom[$i] ?? null; @endphp
                <x-vdbs.forms.form-grid :columns="2">
                    <x-vdbs.forms.input :id="'c-'.$i.'-label'" :name="'custom['.$i.'][label]'" label="Neue Hauptseite: Titel" :optional="true" maxlength="60" :value="$entry['label'] ?? null" />
                    <x-vdbs.forms.input :id="'c-'.$i.'-url'" :name="'custom['.$i.'][url]'" label="Adresse" :optional="true" maxlength="255" placeholder="/seite oder https://…" :value="$entry['url'] ?? null" />
                </x-vdbs.forms.form-grid>
                @for ($j = 0; $j < $childSlots; $j++)
                    @php $child = ($entry['children'] ?? [])[$j] ?? null; @endphp
                    <x-vdbs.forms.form-grid :columns="2">
                        <x-vdbs.forms.input :id="'c-'.$i.'-'.$j.'-label'" :name="'custom['.$i.'][children]['.$j.'][label]'" label="Subpage: Titel" :optional="true" maxlength="60" :value="$child['label'] ?? null" />
                        <x-vdbs.forms.input :id="'c-'.$i.'-'.$j.'-url'" :name="'custom['.$i.'][children]['.$j.'][url]'" label="Subpage: Adresse" :optional="true" maxlength="255" :value="$child['url'] ?? null" />
                    </x-vdbs.forms.form-grid>
                @endfor
            @endfor
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
