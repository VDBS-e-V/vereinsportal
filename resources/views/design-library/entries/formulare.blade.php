<p>Paket <strong>Formulare</strong> · <code>&lt;x-vdbs.forms.input&gt;</code>, <code>password</code>, <code>code</code>, <code>textarea</code>, <code>select</code>, <code>search</code>. Jedes Feld hat ein sichtbares Label, optional Hinweis, Pflicht-/Optional-Kennzeichnung als Text und eine Fehler- oder Erfolgsmeldung mit Icon.</p>

<h2>Zustände</h2>
<x-vdbs.forms.form-grid :columns="2">
    <x-vdbs.forms.input name="s_normal" label="Normal" hint="Ein Hinweis erklärt, was erwartet wird." />
    <x-vdbs.forms.input name="s_required" label="Pflichtfeld" :required="true" />
    <x-vdbs.forms.input name="s_optional" label="Optionales Feld" :optional="true" />
    <x-vdbs.forms.input name="s_filled" label="Mit Wert" value="Erika Musterfrau" />
    <x-vdbs.forms.input name="s_error" label="Fehler" value="falsch" error="Bitte geben Sie eine gültige E-Mail-Adresse ein." type="email" />
    <x-vdbs.forms.input name="s_success" label="Erfolgreich geprüft" value="vdbs" success="Benutzername ist frei." />
    <x-vdbs.forms.input name="s_disabled" label="Deaktiviert" value="Nicht änderbar" disabled />
    <x-vdbs.forms.input name="s_readonly" label="Nur lesen" value="Mitgliedsnummer 12345" readonly />
</x-vdbs.forms.form-grid>
<p>Hover und Fokus: mit Maus und Tab-Taste ausprobieren. Der Fokus zeigt einen Ring von 3 px. Fehler und Erfolg sind nie nur farblich: Rahmen, Icon und Text kommen zusammen.</p>

<h2>Größen</h2>
<x-vdbs.forms.form-grid :columns="3">
    <x-vdbs.forms.input name="size_s" label="Klein (s)" size="s" />
    <x-vdbs.forms.input name="size_m" label="Mittel (m, Standard)" size="m" />
    <x-vdbs.forms.input name="size_l" label="Groß (l)" size="l" />
</x-vdbs.forms.form-grid>

<h2>Typen</h2>
<x-vdbs.forms.form-grid :columns="3">
    <x-vdbs.forms.input name="t_email" label="E-Mail" type="email" autocomplete="email" />
    <x-vdbs.forms.input name="t_tel" label="Telefon" type="tel" autocomplete="tel" />
    <x-vdbs.forms.input name="t_url" label="Webseite" type="url" />
    <x-vdbs.forms.input name="t_number" label="Anzahl" type="number" min="0" />
    <x-vdbs.forms.input name="t_date" label="Datum" type="date" />
    <x-vdbs.forms.input name="t_time" label="Uhrzeit" type="time" />
    <x-vdbs.forms.input name="t_dt" label="Datum und Uhrzeit" type="datetime-local" />
    <x-vdbs.forms.input name="t_month" label="Monat" type="month" />
    <x-vdbs.forms.input name="t_week" label="Woche" type="week" />
</x-vdbs.forms.form-grid>

<h2>Mit Vorsatz oder Nachsatz</h2>
<x-vdbs.forms.form-grid :columns="3">
    <x-vdbs.forms.input name="g_price" label="Betrag" prefix="€" type="number" />
    <x-vdbs.forms.input name="g_weight" label="Gewicht" suffix="kg" type="number" />
    <x-vdbs.forms.input name="g_mail" label="E-Mail mit Icon" prefix-icon="mail" type="email" />
    <x-vdbs.forms.input name="g_both" label="Fehler in der Gruppe" prefix="€" suffix="/ Monat" error="Bitte einen Betrag eingeben." />
</x-vdbs.forms.form-grid>

<h2>Passwort</h2>
<x-vdbs.forms.form-grid :columns="2">
    <x-vdbs.forms.password name="pw_current" label="Aktuelles Passwort" />
    <x-vdbs.forms.password name="pw_new" label="Neues Passwort" :new="true" :rules="['Mindestens 12 Zeichen', 'Keine Wiederholung eines alten Passworts']" />
</x-vdbs.forms.form-grid>
<p>Der Umschalter „Passwort anzeigen“ benötigt JavaScript. Das Feld wird nie vorbelegt. <code>new</code> setzt <code>autocomplete="new-password"</code>.</p>

<h2>Code</h2>
<x-vdbs.forms.code name="code_demo" label="Sicherheitscode" hint="Den 6-stelligen Code aus Ihrer Authenticator-App eingeben." />
<x-vdbs.forms.code name="code_demo8" label="Code mit 8 Zeichen" :length="8" kind="alphanumeric" size="m" />

<h2>Textarea</h2>
<x-vdbs.forms.form-grid :columns="2">
    <x-vdbs.forms.textarea name="ta_plain" label="Nachricht" :rows="4" />
    <x-vdbs.forms.textarea name="ta_counter" label="Mit Zeichenzähler" hint="Höchstens 200 Zeichen." maxlength="200" :counter="true" :rows="4">Vorbelegter Text</x-vdbs.forms.textarea>
</x-vdbs.forms.form-grid>

<h2>Auswahlliste</h2>
<x-vdbs.forms.form-grid :columns="3">
    <x-vdbs.forms.select name="sel_plain" label="Bereich" placeholder="Bitte wählen" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende', 'schulen' => 'Schulen']" />
    <x-vdbs.forms.select name="sel_value" label="Mit Vorauswahl" value="schulen" :options="['vorstand' => 'Vorstand', 'teamende' => 'Teamende', 'schulen' => 'Schulen']" />
    <x-vdbs.forms.select name="sel_error" label="Fehler" placeholder="Bitte wählen" :options="['a' => 'A', 'b' => 'B']" error="Bitte wählen Sie einen Bereich." :required="true" />
    <x-vdbs.forms.select name="sel_groups" label="Mit Gruppen" :options="['Gremien' => ['vorstand' => 'Vorstand', 'beirat' => 'Beirat'], 'Teams' => ['teamende' => 'Teamende']]" />
    <x-vdbs.forms.select name="sel_multi" label="Mehrfachauswahl" :multiple="true" :value="['a', 'c']" :options="['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma', 'd' => 'Delta']" />
    <x-vdbs.forms.select name="sel_disabled" label="Deaktiviert" :options="['a' => 'A']" disabled />
</x-vdbs.forms.form-grid>

<h2>Suche</h2>
<x-vdbs.forms.search name="q_demo" label="Portal durchsuchen" placeholder="Suchbegriff" />

<h2>Eigenschaften</h2>
<ul>
    <li><code>name</code> und <code>label</code> sind Pflicht. Die Id entsteht automatisch (<code>field-&lt;name&gt;</code>), die Fehlermeldung kommt aus dem Validierungsfehler gleichen Namens, der alte Wert nach einem Fehler aus <code>old()</code>.</li>
    <li><code>hint</code>, <code>error</code>, <code>success</code>, <code>required</code>, <code>optional</code>, <code>size</code>, <code>label-hidden</code>.</li>
    <li>Browser-Attribute wie <code>autocomplete</code>, <code>inputmode</code>, <code>maxlength</code>, <code>readonly</code>, <code>disabled</code> gehen direkt an das Feld.</li>
</ul>
