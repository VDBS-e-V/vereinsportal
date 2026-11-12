<p>Paket <strong>Navigation</strong> · <code>&lt;x-vdbs.navigation.stepper&gt;</code></p>

<h2>Horizontal</h2>
<x-vdbs.navigation.stepper label="Zwei-Faktor einrichten" :current="2" :steps="[
    ['label' => 'Methode wählen', 'hint' => 'App oder E-Mail'],
    ['label' => 'Code bestätigen', 'hint' => 'Sechsstelliger Code'],
    ['label' => 'Wiederherstellungscodes', 'hint' => 'Sicher aufbewahren'],
    'Fertig',
]" />

<h2>Vertikal</h2>
<div style="max-width:24rem">
    <x-vdbs.navigation.stepper label="Onboarding" orientation="vertical" :current="3" :steps="['Daten prüfen', 'Einwilligungen', 'Passwort setzen', 'Fertig']" />
</div>
