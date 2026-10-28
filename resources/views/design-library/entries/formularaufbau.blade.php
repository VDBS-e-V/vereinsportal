<p>Paket <strong>Formulare</strong> · <code>form</code>, <code>form-grid</code>, <code>form-section</code>, <code>fieldset</code>, <code>error-summary</code>. Das Formular setzt CSRF und die Methode, schaltet die Browser-Validierung ab (die Meldungen kommen vom Server) und zeigt oben die Fehlerübersicht.</p>

<h2>Fehlerübersicht</h2>
<x-vdbs.forms.error-summary :focus="false" :errors="['email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'role' => 'Bitte wählen Sie eine Rolle.', 'accept' => 'Bitte stimmen Sie den Bedingungen zu.']" />
<p>Die Übersicht erhält beim Laden den Fokus und wird vorgelesen (<code>role="alert"</code>). Jede Meldung springt zum Feld. Sie erscheint automatisch, wenn die Seite Validierungsfehler hat.</p>

<h2>Beispiel: Formular mit Abschnitten</h2>
<x-vdbs.forms.form action="#" method="post" :summary="false" class="vdbs-form--demo">
    <x-vdbs.forms.form-section title="Person" description="Angaben zur Person, die im Portal geführt wird.">
        <x-vdbs.forms.form-grid :columns="2">
            <x-vdbs.forms.input name="demo_first" label="Vorname" :required="true" autocomplete="given-name" />
            <x-vdbs.forms.input name="demo_last" label="Nachname" :required="true" autocomplete="family-name" />
            <x-vdbs.forms.input name="demo_mail" label="E-Mail" type="email" :required="true" autocomplete="email" class="vdbs-form-grid__full" />
            <x-vdbs.forms.input name="demo_birth" label="Geburtsdatum" type="date" :required="true" autocomplete="bday" />
            <x-vdbs.forms.input name="demo_phone" label="Telefon" type="tel" :optional="true" autocomplete="tel" />
        </x-vdbs.forms.form-grid>
    </x-vdbs.forms.form-section>

    <x-vdbs.forms.form-section title="Adresse">
        <x-vdbs.forms.fieldset legend="Anschrift" :optional="true">
            <x-vdbs.forms.form-grid :columns="3">
                <x-vdbs.forms.input name="demo_street" label="Straße" class="vdbs-form-grid__full" autocomplete="street-address" />
                <x-vdbs.forms.input name="demo_zip" label="PLZ" autocomplete="postal-code" inputmode="numeric" />
                <x-vdbs.forms.input name="demo_city" label="Ort" autocomplete="address-level2" />
                <x-vdbs.forms.select name="demo_country" label="Land" value="DE" :options="['DE' => 'Deutschland', 'AT' => 'Österreich', 'CH' => 'Schweiz']" />
            </x-vdbs.forms.form-grid>
        </x-vdbs.forms.fieldset>
    </x-vdbs.forms.form-section>

    <x-vdbs.forms.form-section title="Einwilligung">
        <x-vdbs.forms.checkbox name="demo_accept" label="Ich habe die Datenschutzhinweise gelesen" :required="true" />
    </x-vdbs.forms.form-section>

    <x-slot:actions>
        <x-vdbs.actions.button type="submit">Speichern</x-vdbs.actions.button>
        <x-vdbs.actions.button type="reset" variant="tertiary">Zurücksetzen</x-vdbs.actions.button>
    </x-slot:actions>
</x-vdbs.forms.form>

<h2>Regeln</h2>
<ul>
    <li><strong>Pflicht oder optional:</strong> Entweder die Pflichtfelder oder die optionalen Felder als Text kennzeichnen, je nachdem, was seltener ist. Nie nur mit Stern.</li>
    <li><strong>Reihenfolge der Aktionen:</strong> Hauptaktion zuerst (links), Abbrechen als Tertiär. Am Handy stehen sie untereinander.</li>
    <li><strong>Raster:</strong> <code>form-grid</code> hat 1, 2 oder 3 Spalten und ist am Handy immer einspaltig. Ein Feld über die ganze Breite: Klasse <code>vdbs-form-grid__full</code>.</li>
    <li><strong>Gruppen:</strong> Zusammengehörende Felder und Optionen stehen in einem <code>fieldset</code> mit Legende.</li>
    <li><strong>Methoden:</strong> <code>method="put|patch|delete"</code> setzt automatisch die Methoden-Tarnung, <code>:files="true"</code> den Upload-Typ.</li>
</ul>
