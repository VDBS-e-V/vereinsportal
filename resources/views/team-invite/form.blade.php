@php($crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Bewerbung als Teamende:r']])

<x-layouts.app title="Als Teamende:r bewerben" width="medium">
    <x-vdbs.layout.page-header title="Als Teamende:r bewerben" lead="{{ $seminar->type->label() }}: {{ $seminar->title }}" :breadcrumb="$crumbs" />

    <x-vdbs.data.detail-list layout="inline">
        <x-vdbs.data.detail-item label="Leistung">{{ $seminar->type->label() }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Ort">{{ $seminar->partner?->city ?? '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Erster Termin">{{ $seminar->sessions->first()?->starts_at->format('d.m.Y') ?? '–' }}</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Gesucht">{{ $seminar->team_needed }} Teamende</x-vdbs.data.detail-item>
        <x-vdbs.data.detail-item label="Bewerbung bis">{{ $seminar->application_deadline?->format('d.m.Y') ?? '–' }}</x-vdbs.data.detail-item>
    </x-vdbs.data.detail-list>

    <x-vdbs.forms.form :action="route('team-invite.store', ['token' => $token])" style="margin-top:var(--space-6)">
        <x-vdbs.forms.bot-guard />
        <x-vdbs.forms.form-section title="Ihre Angaben">
            <x-vdbs.forms.form-grid :columns="2">
                <x-vdbs.forms.input name="first_name" label="Vorname" :required="true" autocomplete="given-name" maxlength="100" />
                <x-vdbs.forms.input name="last_name" label="Nachname" :required="true" autocomplete="family-name" maxlength="100" />
                <x-vdbs.forms.input name="email" type="email" label="E-Mail-Adresse" :required="true" autocomplete="email" maxlength="190" hint="Wir schicken einen Link zur Bestätigung." />
                <x-vdbs.forms.input name="phone" type="tel" label="Telefon" :optional="true" autocomplete="tel" maxlength="50" />
                <x-vdbs.forms.input name="birth_year" type="number" label="Geburtsjahr" :optional="true" min="1930" max="{{ (int) now()->format('Y') - 14 }}" hint="Freiwillig, nur für die Teamzusammenstellung." />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.form-section>

        <x-vdbs.forms.form-section title="Ihre Bewerbung">
            <x-vdbs.forms.multi-select name="competences" label="Kompetenzen" :searchable="true" placeholder="Kompetenzen suchen" search-label="Kompetenzen filtern" :optional="true" :options="$competenceOptions" hint="Mehrere möglich." />
            <x-vdbs.forms.textarea name="experience" label="Erfahrung" :optional="true" :rows="3" maxlength="800" :counter="true" />
            <x-vdbs.forms.input name="note" label="Nachricht an die Koordination" :optional="true" maxlength="300" />
            <x-vdbs.forms.checkbox name="wants_lead" label="Ich möchte gern die Seminarleitung übernehmen." />
            <x-vdbs.forms.checkbox name="consent_contact" label="Mein Name und meine Kontaktdaten dürfen an das Team weitergegeben werden." hint="Freiwillig." />
            <x-vdbs.forms.checkbox name="consent_data" label="Ich stimme zu, dass meine Angaben zur Zusammenstellung des Teams verarbeitet werden. Sie werden sechs Monate nach dem Seminar gelöscht." :required="true" />
        </x-vdbs.forms.form-section>

        <x-slot:actions>
            <x-vdbs.actions.button type="submit" icon="check">Bewerbung absenden</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>
</x-layouts.app>
