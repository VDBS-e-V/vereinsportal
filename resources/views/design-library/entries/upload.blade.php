<p>Paket <strong>Formulare</strong> · <code>&lt;x-vdbs.forms.file&gt;</code>. Der Dateidialog des Browsers bleibt erhalten, JavaScript ergänzt die Anzeige der gewählten Dateien.</p>

<h2>Feld</h2>
<x-vdbs.forms.file name="doc" label="Dokument" accept=".pdf,.doc,.docx" hint="PDF oder Word, höchstens 10 MB." />

<h2>Mehrere Dateien</h2>
<x-vdbs.forms.file name="docs" label="Anhänge" :multiple="true" accept="image/*,.pdf" hint="Bilder oder PDF, je höchstens 10 MB." :optional="true" />

<h2>Dropzone</h2>
<x-vdbs.forms.file name="drop" label="Mitgliedsnachweis" variant="dropzone" accept=".pdf,image/*" hint="PDF oder Bild, höchstens 5 MB." />

<h2>Mit Fehler</h2>
<x-vdbs.forms.file name="doc_error" label="Profilbild" accept="image/*" error="Die Datei ist größer als 5 MB." />

<h2>Hinweise</h2>
<ul>
    <li>Der Hinweis nennt erlaubte Typen und die maximale Größe. Die Prüfung findet immer auch auf dem Server statt.</li>
    <li>Für den Upload braucht das Formular <code>:files="true"</code> (<code>enctype="multipart/form-data"</code>).</li>
    <li>Bilder, die im Portal angezeigt werden, brauchen später eine Quelle und einen Alt-Text (Paket Medien).</li>
</ul>
