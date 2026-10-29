<x-account.shell title="Konto löschen" current="deletion" lead="Sie können Ihr Konto und Ihre persönlichen Angaben löschen lassen.">
    @if ($blockers !== [])
        <x-vdbs.feedback.alert variant="warning" title="Das Konto kann zurzeit nicht gelöscht werden">
            @foreach ($blockers as $blocker) {{ $blocker }} @endforeach
        </x-vdbs.feedback.alert>
    @endif

    <x-vdbs.content.prose>
        <h2>Was passiert</h2>
        <ul>
            <li>Ihr Konto ist sofort gesperrt, alle Sitzungen enden.</li>
            <li>Sie haben <strong>{{ $graceDays }} Tage</strong> Zeit, die Löschung über den Link in der E-Mail abzubrechen.</li>
            <li>Danach werden Ihr Name, Ihre E-Mail-Adresse, Ihre Anschrift und Ihre Zugangsdaten unwiderruflich entfernt. Ihre Rollen enden.</li>
            @if ($hasMembership)
                <li>Eine laufende Mitgliedschaft endet mit der Löschung, ein offener Antrag wird abgelehnt. Hochgeladene Dokumente werden gelöscht.</li>
            @endif
            <li>Daten, die der Verein aus rechtlichen Gründen aufbewahren muss, bleiben ohne Bezug zu Ihrem Namen erhalten.</li>
        </ul>
        <p>Wenn Sie später wieder teilnehmen möchten, können Sie sich neu registrieren.</p>
    </x-vdbs.content.prose>

    @if ($blockers === [])
        <p style="margin-top:var(--space-6)">
            <x-vdbs.actions.button variant="danger" icon="trash-2" data-vdbs-dialog-open="delete-account">Konto löschen</x-vdbs.actions.button>
        </p>

        <x-vdbs.overlays.confirm-dialog id="delete-account" title="Konto löschen?" variant="danger" :action="route('account.deletion.request')" :phrase="$phrase" confirm-label="Löschung beantragen">
            <p>Ihr Konto wird gesperrt und nach {{ $graceDays }} Tagen endgültig gelöscht, wenn Sie nicht vorher abbrechen.</p>
            <x-slot:fields>
                <x-vdbs.forms.password id="delete-account-password" name="password" label="Passwort" :required="true" />
            </x-slot:fields>
        </x-vdbs.overlays.confirm-dialog>
    @endif
</x-account.shell>
