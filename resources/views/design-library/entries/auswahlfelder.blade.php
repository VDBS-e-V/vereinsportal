<p>Paket <strong>Formulare</strong> · <code>checkbox</code>, <code>checkbox-group</code>, <code>radio-group</code>, <code>switch</code>. Die Auswahlelemente sind 24 px groß, der ganze Text ist anklickbar, der Zustand ist nie nur eine Farbe.</p>

<h2>Checkbox</h2>
<x-vdbs.forms.checkbox name="c_plain" label="Angemeldet bleiben" />
<x-vdbs.forms.checkbox name="c_checked" label="Newsletter abonnieren" :checked="true" hint="Wir schreiben höchstens einmal im Monat." />
<x-vdbs.forms.checkbox name="c_required" label="Ich habe die Datenschutzhinweise gelesen" :required="true" />
<x-vdbs.forms.checkbox name="c_error" label="Ich stimme den Teilnahmebedingungen zu" :required="true" error="Bitte stimmen Sie den Teilnahmebedingungen zu." />
<x-vdbs.forms.checkbox name="c_disabled" label="Deaktiviert" disabled />
<x-vdbs.forms.checkbox name="c_mixed" label="Alle auswählen (gemischter Zustand)" :indeterminate="true" />

<h2>Checkbox-Gruppe</h2>
<x-vdbs.forms.checkbox-group name="areas" legend="Interessensbereiche" hint="Mehrfachauswahl möglich." :value="['teamende']" :options="[
    'vorstand' => 'Vorstand',
    'teamende' => ['label' => 'Teamende', 'hint' => 'Einsätze, Materialien und Abrechnung'],
    'schulen' => 'Schulen',
    'archiv' => ['label' => 'Archiv', 'disabled' => true],
]" />

<h2>Radio-Gruppe</h2>
<x-vdbs.forms.form-grid :columns="2">
    <x-vdbs.forms.radio-group name="role_list" legend="Rolle (Liste)" value="member" :required="true" :options="['member' => 'Mitglied', 'team' => 'Teamende:r', 'school' => 'Schule']" />
    <x-vdbs.forms.radio-group name="role_inline" legend="Rolle (nebeneinander)" layout="inline" value="team" :options="['member' => 'Mitglied', 'team' => 'Teamende:r', 'school' => 'Schule']" />
</x-vdbs.forms.form-grid>

<h2>Radio als Optionskarten</h2>
<x-vdbs.forms.radio-group name="plan" legend="Zugangsart" layout="cards" value="mail" hint="Wie möchten Sie den zweiten Faktor erhalten?" :options="[
    'totp' => ['label' => 'Authenticator-App', 'hint' => 'Empfohlen: Code aus einer App'],
    'mail' => ['label' => 'E-Mail-Code', 'hint' => 'Code per E-Mail an Ihre Adresse'],
    'recovery' => ['label' => 'Wiederherstellungscodes', 'hint' => 'Einmalcodes zum Ausdrucken'],
]" />

<h2>Radio mit Fehler</h2>
<x-vdbs.forms.radio-group name="role_error" legend="Rolle" :required="true" error="Bitte wählen Sie eine Rolle." :options="['member' => 'Mitglied', 'team' => 'Teamende:r']" />

<h2>Schalter</h2>
<x-vdbs.forms.switch name="sw_mail" label="E-Mail-Benachrichtigungen" :checked="true" hint="Gilt sofort, ohne Speichern." />
<x-vdbs.forms.switch name="sw_off" label="Zwei-Faktor-Anmeldung" />
<x-vdbs.forms.switch name="sw_dis" label="Deaktivierter Schalter" disabled />
<p>Der Schalter ist technisch eine Checkbox mit <code>role="switch"</code>. Neben dem Schalter steht der Zustand als Text (An/Aus).</p>
