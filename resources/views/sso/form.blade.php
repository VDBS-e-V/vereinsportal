@php
    $editing = $client !== null;
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Admins'], ['label' => 'Anbindungen', 'href' => route('admin.oauth.index')], ['label' => $editing ? $client->name : 'Neue Anbindung']];
    $roles = old('roles', $setting?->allowed_roles ?? $defaultRoles);
@endphp
<x-layouts.app :title="$editing ? 'Anbindung bearbeiten' : 'Neue Anbindung'" width="medium">
    <x-vdbs.layout.page-header :title="$editing ? 'Anbindung bearbeiten' : 'Neue Anbindung'" lead="Nach dem Speichern sehen Sie Client-ID und Geheimnis einmalig. Tragen Sie beides im anderen System ein." :breadcrumb="$crumbs" />

    <x-vdbs.forms.form :action="$editing ? route('admin.oauth.update', $client->getKey()) : route('admin.oauth.store')" :method="$editing ? 'put' : 'post'">
        @error('form') <x-vdbs.feedback.alert variant="error">{{ $message }}</x-vdbs.feedback.alert> @enderror
        <x-vdbs.forms.form-section title="Das System">
            <x-vdbs.forms.input name="name" label="Name" :required="true" maxlength="100" :value="$client?->name" hint="z. B. Nextcloud oder Moodle" />
            <x-vdbs.forms.textarea name="redirect_uris" label="Umleitungsadressen (eine pro Zeile)" :required="true" :rows="3" :value="$client ? implode("\n", $client->redirect_uris) : null" hint="Die Adresse, zu der das andere System nach der Anmeldung zurückkehrt. Nur https://, ohne Platzhalter." />
            <x-vdbs.forms.textarea name="description" label="Beschreibung" :optional="true" :rows="2" maxlength="1000" :value="$setting?->description" />
            <x-vdbs.forms.input name="contact" label="Ansprechperson beim System" :optional="true" maxlength="190" :value="$setting?->contact" />
        </x-vdbs.forms.form-section>
        <x-vdbs.forms.form-section title="Wer darf sich anmelden?">
            <x-vdbs.forms.checkbox-group name="roles" legend="Rollen" :required="true" :options="$roleOptions" :value="$roles" hint="Mehrfachauswahl. Wer keine dieser Rollen hat, kommt nicht hinein." />
        </x-vdbs.forms.form-section>
        <x-vdbs.forms.form-section title="Technik">
            @unless ($editing)
                <x-vdbs.forms.checkbox name="confidential" label="Vertraulicher Client (mit Geheimnis)" :checked="true" hint="Für Nextcloud und Moodle richtig. Nur Apps ohne eigenen Server brauchen die öffentliche Art." />
            @endunless
            <x-vdbs.forms.checkbox name="show_consent" label="Zustimmungsseite anzeigen" :checked="old('show_consent', $setting?->show_consent ?? true)" hint="Für Systeme des Vereins können Sie die Seite abschalten." />
        </x-vdbs.forms.form-section>
        <x-slot:actions><x-vdbs.actions.button type="submit" icon="check">Speichern</x-vdbs.actions.button></x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
