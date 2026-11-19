# Mail-Builder

Der Mail-Builder (Modul `MailBuilder`) baut Mails aus Blöcken, hält ein zentrales Mail-Design und macht die Texte aller automatischen Mails (Systemmails) bearbeitbar. Seite: „Geschäftsstelle › Inhalte › Systemmails“.

## Bausteine

- **Blockformat** (`BlockSchema`): `{version, preheader, blocks:[{id, type, props, visible_if}]}`. `sanitize()` macht aus jeder Eingabe ein gültiges Dokument: unbekannte Blöcke fallen weg, Rich-Text läuft durch die Positivliste von `RichText`, Ziele müssen `https`, `http`, `mailto`, `tel` oder ein Platzhalter sein.
- **Blocktypen:** Überschrift, Text, Button, Bild, Trennlinie, Abstand, Hinweisbox, Code-Box, Angaben-Tabelle, Aufzählung, Zitat, zwei Spalten, Signatur, Wiederholer (für Listen wie Termine). Jeder Block kann an einen Platzhalter gebunden werden („nur wenn ausgefüllt/leer“).
- **Renderer** (`MailRenderer`): tabellenbasiertes HTML mit Inline-Styles, 600 px, mobiler Anpassung, Dunkelmodus und Vorschautext, dazu eine Textfassung. Platzhalter werden maskiert; als „raw“ gekennzeichnete Werte sind fertiges HTML aus dem Code.
- **Prüfungen** (`MailCheck`): unbekannte und fehlende Pflichtplatzhalter, fehlende Alternativtexte, Kontraste (WCAG), leere Blöcke. Fehler verhindern das Speichern, Hinweise nicht.
- **Mail-Design** (`MailLayout`, `LayoutSettings`): Farben, Systemschrift, Breite, Ecken, Logo, Kopftext, Fußzeile. Ein Standarddesign für alle Mails; ohne gespeicherten Eintrag gelten die Vorgaben.
- **Bilder** (`MailAsset`): PNG, JPG, GIF bis 2 MB und 2400 Pixel Breite, kein SVG. Liegen öffentlich unter `storage/mail-assets` (`php artisan storage:link` muss laufen). Benutzte Bilder lassen sich nicht löschen.

## Systemmails

Das Register `SystemMails` enthält je Mail: Schlüssel, Gruppe, Standardbetreff, Standardtext (Blöcke), Platzhalter mit Beispielwerten und geschützte Platzhalter (Links, Codes). Die Mailables erben von `TemplatedMail` und liefern nur `systemKey()` und `values()`; Anhänge und Kopfzeilen bleiben in der Klasse.

- **Rückfall:** `SystemMailRenderer` nimmt die gespeicherte Änderung (`mail_templates.system_key`), wenn sie veröffentlicht und fehlerfrei ist, sonst den Standardtext aus dem Register. Eine Mail geht also immer raus.
- **Sicherheitsmails** (Passwort, Anmeldecode, E-Mail-Änderung, Kontolöschung, Einladung, Registrierung, persönlicher Zugang): bearbeiten darf nur die Administration (`mail_system.security`); die Geschäftsstelle (`mail_system.manage`) sieht sie nur.
- **Versionen:** Jedes Speichern legt den alten Stand ab (20 Stände), Wiederherstellen und „Auf Standard zurücksetzen“ sind möglich. Änderungen stehen im Protokoll.
- **Neue Systemmail anlegen:** Eintrag in `SystemMails::build()`, Mailable von `TemplatedMail` ableiten. Der Test „Register für jede Mail“ prüft, dass der Schlüssel existiert und der Standardtext ohne Fehler rendert.

## Seminarmitteilungen

`SeminarNoticeMail` nutzt einen gemeinsamen Rahmen (`seminars.notice`: Anrede, Text des Anlasses, Link). Der Wortlaut der einzelnen Anlässe (Zusage, Absage, Erinnerung …) steht noch im Ablauf (`SeminarNotifications`); eigene Texte je Anlass sind ein möglicher nächster Schritt.

## Grenzen

- Die Darstellung in Outlook, Gmail und Apple Mail lässt sich nur mit echten Testmails prüfen („Testmail an mich“ im Builder).
- Rundschreiben tragen das zentrale Mail-Design (Kopf, Farben, Fußzeile) und behalten den bisherigen Texteditor; ihr Text ist ein Textblock, Hinweis zum Empfängerkreis und Abmeldelink stehen in der Fußzeile. Der Aufbau aus Blöcken für Rundschreiben und Vorlagen steht noch aus.
