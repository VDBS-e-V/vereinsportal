@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Teamende'], ['label' => 'Profil']];
@endphp

<x-layouts.app title="Mein Profil" width="medium">
    <x-vdbs.layout.page-header title="Mein Profil als Teamende:r" lead="Die Koordination nutzt diese Angaben, um Teams für Seminare zusammenzustellen. Sie sind nur für Sie und die Koordination sichtbar." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="route('team-profile.update')" method="put">
        <x-vdbs.forms.checkbox-group name="competences" legend="Kompetenzen" :optional="true" :options="$competences" :value="$profile?->competences ?? []" hint="Wählen Sie, worin Sie sich sicher fühlen." />

        <x-vdbs.forms.textarea name="experience" label="Erfahrungen" :optional="true" maxlength="1000" :counter="true" :rows="5" :value="$profile?->experience" hint="Z. B. frühere Seminare, Engagement in der SV, Schulungen" />
        <x-vdbs.forms.checkbox name="lead_experience" label="Ich habe Erfahrung in der Seminarleitung" :checked="(bool) ($profile?->lead_experience)" />

        <x-vdbs.forms.radio-group name="gender" legend="Geschlecht (freiwillig)" :optional="true" layout="inline" :options="['' => 'keine Angabe'] + $genders" :value="$profile?->gender?->value ?? ''" hint="Nur die Koordination sieht diese Angabe, ausschließlich für ausgewogen zusammengesetzte Teams. Sie können sie jederzeit ändern oder entfernen." />

        <p style="color:var(--text-muted)">Ihr Alter entnimmt die Koordination dem Geburtsdatum in Ihren Stammdaten{{ $birth ? '' : ' (dort ist keines eingetragen)' }}.</p>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" :href="route('seminar-applications.index')">Zu den Seminaren</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
