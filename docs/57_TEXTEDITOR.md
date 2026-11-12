# 57 – Texteditor (TinyMCE, selbst gehostet)

Formatierte Texte werden mit **TinyMCE** bearbeitet, und zwar in der **Community-Ausgabe**, die der eigene Server ausliefert (`/vendor/tinymce`). Es gibt **keinen Cloud-Dienst, kein CDN und keinen API-Schlüssel**; es werden keine Daten an Dritte gesendet.

## Einrichtung

```
npm install        # holt tinymce und tinymce-i18n (deutsche Oberfläche)
npm run build      # kopiert TinyMCE nach public/vendor/tinymce und baut die Oberfläche
```

`scripts/copy-tinymce.mjs` läuft automatisch vor jedem `npm run build` und kopiert nur das Nötige (Kern, Oberfläche „oxide“ hell und dunkel, Plugins `lists`, `link`, `autolink`, deutsche Sprachdatei). `public/vendor/tinymce` steht in `.gitignore`; auf dem Server läuft der Build ebenfalls. Fehlt TinyMCE (z. B. noch kein `npm install`), bleiben die Felder **einfache Textfelder**, nichts bricht.

**Lizenz:** TinyMCE 7 steht unter GPL-2.0-or-later (Community-Ausgabe, `license_key: 'gpl'`). Wir betreiben das Portal als Webanwendung und geben den Code nicht weiter; bei einer Weitergabe der Software ist die GPL zu beachten. Der Vorstand sollte das einmal zur Kenntnis nehmen.

## Wo der Editor eingesetzt wird

Bausteine: Komponente `<x-vdbs.forms.textarea :rich="true">`, Skript `resources/js/richtext.js`, Bereinigung `App\Support\Html\RichText`, Ausgabe mit `{!! rich_text($text) !!}`.

| Feld | Ausgabe |
|---|---|
| Artikel: Text | öffentliche Artikelseite, Anreißer |
| Seiten (Impressum, Datenschutz, …): Text | öffentliche Seite |
| Veranstaltung: Beschreibung | Anmeldeseite, Liste (als reiner Text) |
| Mitteilung an Angemeldete: Text | E-Mail |
| Seminar: Notiz | Seminarseite |
| Schule: Notiz | Schulseite, CSV-Export (als reiner Text) |
| Schule: Anfrage, „Weitere Hinweise“ | Notiz des Seminars |

**Bewusst ohne Editor** bleiben Felder, deren Text als reiner Text weiterverwendet wird: das öffentliche Kontaktformular (geht als Nur-Text-Mail raus und ist öffentlich), kurze Notizen und Begründungen im Verlauf (Zeitleiste, Ablehnungsgründe), die Standardtexte der Kostenvoranschläge (Platzhalter, Druckzeilen), Profil-Freitexte der Teamenden sowie Einwilligungs- und Dokumentnotizen. Weitere Felder lassen sich nach demselben Muster umstellen (`:rich="true"`, beim Speichern `RichText::clean()` bzw. `normalize()`, bei der Ausgabe `rich_text()`).

## Sicherheit

- Der Editor liefert HTML. Das wird **beim Speichern und beim Ausgeben** über eine **Positivliste** bereinigt (`RichText::sanitize`): erlaubt sind Absätze, Zeilenumbruch, Überschriften (h2 bis h4), fett, kursiv, unterstrichen, Listen, Zitat, Trennlinie und **Links** (`http`, `https`, `mailto`, `tel` und relative Adressen, mit `rel="noopener noreferrer"`). Alles andere wird entfernt, auch alle Attribute außer `href`; Skripte, Formulare, Frames, Bilder und Tabellen fallen weg. Der Editor selbst ist auf dieselbe Liste eingestellt, die Prüfung auf dem Server entscheidet aber immer.
- **Leere Eingaben:** Der Editor schickt „`<p></p>`“; das gilt serverseitig als leer, „Pflichtfeld“ greift also.
- **Ältere Texte** (Markdown oder einfacher Text) bleiben unverändert gespeichert und werden beim Ausgeben als Markdown ohne rohes HTML dargestellt. Im Editor erscheinen sie als formatierter Text und werden beim nächsten Speichern in HTML umgewandelt. Eine Datenmigration ist nicht nötig.
- Längenprüfungen gelten für den gespeicherten HTML-Text (großzügige Grenzen, z. B. 60.000 Zeichen bei Artikeln); der Zähler entfällt im Editor.
- Bilder, Dateien und eingebettete Inhalte gibt es im Editor bewusst nicht.
