<div style="max-width:28rem">
    <x-vdbs.forms.input name="demo_name" label="Name" hint="Vor- und Nachname" />
    <x-vdbs.forms.input name="demo_mail" label="E-Mail" type="email" value="falsch" error="Bitte eine gültige E-Mail-Adresse eingeben." />
    <x-vdbs.forms.input name="demo_ok" label="Benutzername" value="vdbs" success="Benutzername ist frei." />
    <x-vdbs.forms.input name="demo_off" label="Mitgliedsnummer" value="12345" disabled />
    <x-vdbs.forms.select name="demo_select" label="Bereich"><option>Vorstand</option><option>Mitglieder</option></x-vdbs.forms.select>
    <x-vdbs.forms.textarea name="demo_text" label="Nachricht" />
</div>
