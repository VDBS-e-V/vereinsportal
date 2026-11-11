@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Vorstand'], ['label' => 'Kalender']];
@endphp

<x-layouts.app title="Vorstandskalender" width="wide">
    <x-vdbs.layout.page-header title="Kalender der Vorstandssitzungen" lead="Ein gemeinsamer Kalender für alle Vorstandsmitglieder. Der Link ist nur für den Vorstand sichtbar." :breadcrumb="$crumbs" />

    @if ($settings['calendar_url'] !== '')
        <p><x-vdbs.actions.button :href="$settings['calendar_url']" icon="calendar" :external="true">Kalender öffnen</x-vdbs.actions.button></p>
    @else
        <x-vdbs.feedback.alert variant="info">Es ist noch kein Kalenderlink hinterlegt. Tragen Sie ihn unten ein.</x-vdbs.feedback.alert>
    @endif

    @if ($settings['notes'] !== '')
        <p style="white-space:pre-line">{{ $settings['notes'] }}</p>
    @endif

    @if ($webcal)
        <x-vdbs.layout.section title="Im eigenen Kalender abonnieren">
            <p>Mit dieser Adresse erscheinen die Vorstandssitzungen in Ihrem Kalenderprogramm (Outlook, Apple Kalender, Thunderbird, Google Kalender).</p>
            <p><x-vdbs.actions.button variant="secondary" :href="$webcal" icon="calendar">Abonnieren</x-vdbs.actions.button></p>
            <x-vdbs.forms.input name="feed_copy" label="Abo-Adresse zum Kopieren" :value="$settings['feed_url']" readonly />
        </x-vdbs.layout.section>
    @endif

    <x-vdbs.layout.section title="Link festlegen">
        <x-vdbs.forms.form :action="route('board.calendar.update')" method="put">
            <x-vdbs.forms.input name="calendar_url" label="Link zum Kalender" type="url" maxlength="500" :value="old('calendar_url', $settings['calendar_url'])" hint="Zum Beispiel die Freigabe-Adresse Ihres Kalenders in Nextcloud, Google oder Outlook. Sie muss mit https:// beginnen." />
            <x-vdbs.forms.input name="feed_url" label="Abo-Adresse (iCal), optional" type="url" maxlength="500" :value="old('feed_url', $settings['feed_url'])" hint="Die Adresse zum Einbinden in das eigene Kalenderprogramm, meist mit der Endung .ics." />
            <x-vdbs.forms.textarea name="notes" label="Hinweis, optional" maxlength="1000" :rows="3" :value="old('notes', $settings['notes'])" hint="Zum Beispiel Sitzungsrhythmus oder Treffpunkt." />
            <x-slot:actions><x-vdbs.actions.button type="submit" icon="save">Speichern</x-vdbs.actions.button></x-slot:actions>
        </x-vdbs.forms.form>
        <p class="vdbs-hint">Geben Sie nur Adressen frei, die der Vorstand sehen darf: Wer den Link kennt, kann je nach Freigabe den Kalender öffnen.</p>
    </x-vdbs.layout.section>
</x-layouts.app>
