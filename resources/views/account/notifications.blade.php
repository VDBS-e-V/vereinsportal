<x-account.shell title="Benachrichtigungen" current="notifications" lead="Legen Sie fest, welche E-Mails Sie vom Portal bekommen. Die Auswahl gilt nur für Sie.">
    <section aria-labelledby="required-heading">
        <h2 id="required-heading" style="font-size:var(--font-size-h3)">Immer aktiv</h2>
        <p>Mails zu Anmeldung, Sicherheit und Konto lassen sich nicht abschalten: Zwei-Faktor-Codes, Passwort zurücksetzen, Änderung der E-Mail-Adresse, Löschung des Kontos und Einladungen. Sie schützen Ihr Konto.</p>
    </section>

    @if ($groups === [])
        <p style="margin-top:var(--space-6)">Für Ihre Rollen gibt es zurzeit keine weiteren Benachrichtigungen, die Sie einstellen können.</p>
    @else
        <x-vdbs.forms.form :action="route('account.notifications.update')" method="put" style="margin-top:var(--space-6)">
            @foreach ($groups as $group => $entries)
                <x-vdbs.forms.form-section :title="$group">
                    @foreach ($entries as $key => $entry)
                        <x-vdbs.forms.switch :id="'notification-'.$key" :name="'notifications[]'" :value="$key" :label="$entry['label']" :hint="$entry['description']" :checked="$entry['enabled']" />
                    @endforeach
                </x-vdbs.forms.form-section>
            @endforeach

            <x-slot:actions>
                <x-vdbs.actions.button type="submit">Speichern</x-vdbs.actions.button>
            </x-slot:actions>
        </x-vdbs.forms.form>
    @endif
</x-account.shell>
