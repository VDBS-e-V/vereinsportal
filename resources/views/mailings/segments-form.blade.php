@php
    $editing = $existing !== null;
    $pageTitle = $editing ? 'Verteiler bearbeiten' : 'Neuer Verteiler';
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Geschäftsstelle'], ['label' => 'Verteiler', 'href' => route('mailings.segments.index')], ['label' => $pageTitle]];
    $f = $filters;
    $yes = ['' => 'egal'];
@endphp

<x-layouts.app :title="$pageTitle" width="wide">
    <x-vdbs.layout.page-header :title="$pageTitle" lead="Ein Verteiler speichert die Regel, nicht die Adressen: Beim Versand wird er neu berechnet, Neuzugänge sind also dabei. Filter sind UND-verknüpft, mehrere Werte innerhalb eines Filters ODER-verknüpft." :breadcrumb="$crumbs" />

    @if ($preview)
        <x-vdbs.feedback.alert :variant="$preview['count'] === 0 ? 'warning' : 'info'">
            <strong>Vorschau: {{ $preview['count'] }} {{ $preview['count'] === 1 ? 'Person' : 'Personen' }} erreichbar.</strong>
            Nicht angeschrieben: {{ $preview['unsubscribed'] }} Abgemeldete, {{ $preview['suppressed'] }} gesperrte Adressen.
            @if ($preview['count'] === 0)
                Mit diesen Angaben erreicht der Verteiler niemanden. Prüfen Sie die Filter.
            @else
                Beispiele: {{ implode(', ', $preview['names']) }}@if ($preview['count'] > count($preview['names'])) …@endif
            @endif
        </x-vdbs.feedback.alert>
    @endif

    <x-vdbs.forms.form :action="$editing ? route('mailings.segments.update', $existing) : route('mailings.segments.store')" :method="$editing ? 'put' : 'post'">
        <x-vdbs.forms.form-section title="Verteiler">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="120" :value="$segment->name" hint="z. B. Teamende Berlin/Brandenburg ohne Einarbeitung" />
            <x-vdbs.forms.input name="description" label="Beschreibung" :optional="true" maxlength="250" :value="$segment->description" />
            <x-vdbs.forms.multi-select name="visible_to" label="Nutzbar von" :searchable="false" :options="$areaOptions" :value="$segment->visible_to ?? []" hint="Die Koordination kann nur Verteiler nutzen, die ausschließlich Teamende, Schulen, Koordination und Bildungsreferenten enthalten." />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Gruppen">
            <x-vdbs.forms.multi-select name="groups" label="Gruppen" :searchable="true" placeholder="Gruppen wählen" search-label="Gruppen filtern" :options="$groupOptions" :value="$segment->groups()" />
            <x-vdbs.forms.multi-select name="exclude" label="Außer" :optional="true" :searchable="true" placeholder="Gruppen ausnehmen" search-label="Gruppen filtern" :options="$groupOptions" :value="$segment->exclude()" />
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Filter">
            <x-vdbs.forms.multi-select name="federal_state" label="Bundesland der Schule" :optional="true" :searchable="true" placeholder="Bundesländer wählen" search-label="Bundesländer filtern" :options="$federalStates" :value="$f['federal_state'] ?? []" hint="Trifft auf Ansprechpersonen und Konten der Schulen zu." />
            <x-vdbs.forms.multi-select name="school_type" label="Schulart" :optional="true" :searchable="true" placeholder="Schularten wählen" search-label="Schularten filtern" :options="$schoolTypes" :value="$f['school_type'] ?? []" />
            <x-vdbs.forms.select name="seminar_id" label="Seminar" :optional="true" placeholder="Kein Seminarfilter" :options="$seminars" :value="$f['seminar_id'] ?? null" />
            <x-vdbs.forms.select name="seminar_status" label="Beim Seminar" :optional="true" placeholder="Beworben oder bestätigt" :options="['applied' => 'Nur beworben', 'confirmed' => 'Nur im Team bestätigt']" :value="$f['seminar_status'] ?? null" />
            <x-vdbs.forms.select name="onboarding" label="Einarbeitung" :optional="true" placeholder="egal" :options="['open' => 'Noch offen', 'done' => 'Abgeschlossen']" :value="$f['onboarding'] ?? null" />
            <x-vdbs.forms.select name="profile" label="Teamprofil" :optional="true" placeholder="egal" :options="['complete' => 'Vollständig', 'incomplete' => 'Unvollständig oder fehlt']" :value="$f['profile'] ?? null" />
            <x-vdbs.forms.select name="applied" label="Bewerbungen" :optional="true" placeholder="egal" :options="['recent' => 'Bewerbung in den letzten 12 Monaten', 'never' => 'Noch nie beworben']" :value="$f['applied'] ?? null" />
            <x-vdbs.forms.multi-select name="membership_status" label="Mitgliedsstatus" :optional="true" placeholder="Status wählen" :options="['requested' => 'Antrag', 'active' => 'Mitglied', 'ended' => 'Beendet', 'rejected' => 'Abgelehnt']" :value="$f['membership_status'] ?? []" hint="Mitglieder erreichen nur Verteiler, die der Vorstand angelegt hat." />
            <x-vdbs.forms.input name="member_since_from" label="Mitglied seit Jahr (von)" :optional="true" type="number" :value="$f['member_since']['from'] ?? null" />
            <x-vdbs.forms.input name="member_since_to" label="Mitglied seit Jahr (bis)" :optional="true" type="number" :value="$f['member_since']['to'] ?? null" />
            <x-vdbs.forms.input name="postal_prefix" label="Postleitzahl beginnt mit" :optional="true" maxlength="40" :value="isset($f['postal_prefix']) ? implode(', ', (array) $f['postal_prefix']) : null" hint="Anfang der Postleitzahl, z. B. 10 oder 101; mehrere durch Komma trennen." />
            <x-vdbs.forms.multi-select name="age_group" label="Altersgruppe" :optional="true" placeholder="Altersgruppen wählen" :options="$ageGroups" :value="$f['age_group'] ?? []" hint="Aus dem Geburtsdatum der Person." />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button type="submit" variant="secondary" formaction="{{ $editing ? route('mailings.segments.preview-existing', $existing) : route('mailings.segments.preview') }}" formmethod="post">Vorschau ohne Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="text" :href="route('mailings.segments.index')">Zurück zur Liste</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
