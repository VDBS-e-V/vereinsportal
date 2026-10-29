<p>Paket <strong>Disclosure</strong> · <code>&lt;x-vdbs.disclosure.accordion&gt;</code>, <code>&lt;x-vdbs.disclosure.accordion-item&gt;</code></p>
<p>Auf Basis von <code>&lt;details&gt;</code>: ohne JavaScript und per Tastatur bedienbar. Mit <code>exclusive</code> ist immer nur ein Abschnitt offen.</p>

<h2>Mehrere gleichzeitig offen</h2>
<x-vdbs.disclosure.accordion>
    <x-vdbs.disclosure.accordion-item title="Wie melde ich mich an?" :open="true"><p>Mit deiner E-Mail-Adresse und deinem Passwort, danach mit dem Code aus der Authenticator-App.</p></x-vdbs.disclosure.accordion-item>
    <x-vdbs.disclosure.accordion-item title="Ich habe mein Passwort vergessen"><p>Auf der Anmeldeseite „Passwort vergessen“ wählen. Du erhältst einen Link per E-Mail.</p></x-vdbs.disclosure.accordion-item>
    <x-vdbs.disclosure.accordion-item title="Wer sieht meine Daten?"><p>Nur Personen mit der passenden Rolle. Details stehen in der Datenschutzerklärung.</p></x-vdbs.disclosure.accordion-item>
</x-vdbs.disclosure.accordion>

<h2>Immer nur einer offen (exclusive)</h2>
<x-vdbs.disclosure.accordion exclusive="faq-beispiel">
    <x-vdbs.disclosure.accordion-item title="Mitgliedschaft"><p>Informationen zur Mitgliedschaft.</p></x-vdbs.disclosure.accordion-item>
    <x-vdbs.disclosure.accordion-item title="Beitrag"><p>Informationen zum Beitrag.</p></x-vdbs.disclosure.accordion-item>
    <x-vdbs.disclosure.accordion-item title="Kündigung"><p>Informationen zur Kündigung.</p></x-vdbs.disclosure.accordion-item>
</x-vdbs.disclosure.accordion>
