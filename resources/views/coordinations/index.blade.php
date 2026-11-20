@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Verwaltung'], ['label' => 'Koordinationen']];
    $statusOptions = ['1' => 'aktiv', '0' => 'inaktiv'];
@endphp

<x-layouts.app title="Koordinationen" width="wide">
    <x-vdbs.layout.page-header title="Koordinationen" lead="Jede Schule gehört zu einer Koordination (zum Beispiel Neukölln). Die Koordination bestimmt später auch den Ordner eines Seminars in der Nextcloud. Eine Koordination mit Schulen lässt sich nicht löschen, nur auf „inaktiv“ setzen." :breadcrumb="$crumbs" />

    @error('coordination')
        <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert>
    @enderror

    <x-vdbs.forms.form :action="route('coordinations.store')" summaryTitle="Bitte prüfen Sie die neue Koordination">
        <x-vdbs.forms.form-section title="Neue Koordination">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="old('name')" />
                <x-vdbs.forms.input name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="old('sort_order', 0)" hint="Kleine Zahlen stehen oben." />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="plus">Anlegen</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>

    <h2 style="font-size:var(--font-size-h3);margin-top:var(--space-8)">Vorhandene Koordinationen</h2>

    @forelse ($coordinations as $coordination)
        <x-vdbs.forms.form :action="route('coordinations.update', $coordination)" method="put" :summary="false" style="margin-bottom:var(--space-5)">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.input :id="'c-'.$coordination->id.'-name'" name="name" label="Name" :required="true" maxlength="120" :value="$coordination->name" />
                <x-vdbs.forms.input :id="'c-'.$coordination->id.'-sort'" name="sort_order" label="Reihenfolge" type="number" :optional="true" :value="$coordination->sort_order" />
                <x-vdbs.forms.select :id="'c-'.$coordination->id.'-active'" name="active" label="Status" :options="$statusOptions" :value="$coordination->active ? '1' : '0'" />
            </x-vdbs.forms.form-grid>
            <p class="vdbs-hint">{{ $coordination->partners_count }} {{ $coordination->partners_count === 1 ? 'Schule' : 'Schulen' }}</p>
            <x-slot:actions><x-vdbs.actions.button type="submit" size="s" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
        @if ($coordination->partners_count === 0)
            <form method="post" action="{{ route('coordinations.destroy', $coordination) }}" style="margin:calc(var(--space-4) * -1) 0 var(--space-5)">@csrf @method('delete')
                <x-vdbs.actions.button type="submit" size="s" variant="danger-text">„{{ $coordination->name }}“ löschen</x-vdbs.actions.button>
            </form>
        @endif
    @empty
        <x-vdbs.feedback.empty-state title="Noch keine Koordination" icon="map-pin"><p>Legen Sie oben die erste Koordination an.</p></x-vdbs.feedback.empty-state>
    @endforelse
</x-layouts.app>
