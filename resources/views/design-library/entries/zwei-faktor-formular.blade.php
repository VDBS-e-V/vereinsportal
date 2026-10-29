<p>Muster für die Zwei-Faktor-Anmeldung: Methodenwahl, Code-Eingabe und Wiederherstellungscode.</p>

<div style="max-width:var(--container-narrow)">
    <h2>Code eingeben</h2>
    <x-vdbs.forms.form action="#" :summary="false">
        <x-vdbs.forms.code name="code_2fa" label="Sicherheitscode" hint="Den 6-stelligen Code aus Ihrer Authenticator-App eingeben." />
        <x-vdbs.forms.checkbox name="trust_2fa" label="Diesem Gerät 30 Tage vertrauen" />
        <x-slot:actions>
            <x-vdbs.actions.button type="submit">Bestätigen</x-vdbs.actions.button>
            <x-vdbs.actions.button variant="tertiary" href="#">Anderen Weg wählen</x-vdbs.actions.button>
        </x-slot:actions>
    </x-vdbs.forms.form>

    <h2>Zustand: falscher Code</h2>
    <x-vdbs.forms.code name="code_2fa_error" label="Sicherheitscode" error="Der Code ist nicht gültig oder abgelaufen." />

    <h2>Methode wählen</h2>
    <x-vdbs.forms.radio-group name="method_2fa" legend="Zweiter Faktor" layout="cards" value="totp" :options="[
        'totp' => ['label' => 'Authenticator-App', 'hint' => 'Empfohlen'],
        'mail' => ['label' => 'E-Mail-Code', 'hint' => 'Code per E-Mail'],
    ]" />
</div>

<h2>Wiederherstellungscodes</h2>
<p><code>&lt;x-vdbs.data.code-list&gt;</code> zeigt Einmalcodes in Festbreitenschrift, gut lesbar und druckbar.</p>
<x-vdbs.data.code-list :codes="['ABCDE-FGHJK', '23456-789AB', 'KMNPQ-RSTUV', 'WXYZ2-34567', '89ABC-DEFGH', 'JKMNP-QRSTU']" label="Beispielcodes" />

<h2>Einrichtung mit Schritt-Anzeige</h2>
<x-vdbs.navigation.stepper label="Einrichtung" :current="1" :steps="['App verbinden und Code bestätigen', 'Wiederherstellungscodes sichern']" />
