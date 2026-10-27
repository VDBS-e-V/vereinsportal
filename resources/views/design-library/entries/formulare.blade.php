<div style="max-width:28rem">
    <x-vdbs.input name="demo_name" label="Name" hint="Vor- und Nachname" />
    <x-vdbs.input name="demo_mail" label="E-Mail" type="email" value="falsch" error="Bitte eine gültige E-Mail-Adresse eingeben." />
    <x-vdbs.input name="demo_ok" label="Benutzername" value="vdbs" success="Benutzername ist frei." />
    <x-vdbs.input name="demo_off" label="Mitgliedsnummer" value="12345" disabled />
    <x-vdbs.select name="demo_select" label="Bereich"><option>Vorstand</option><option>Mitglieder</option></x-vdbs.select>
    <x-vdbs.textarea name="demo_text" label="Nachricht" />
</div>
