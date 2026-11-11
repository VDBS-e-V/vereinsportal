# Briefvorlagen aus Word (Paket L, Teil 1)

Briefe gestalten Sie in **Word**. An die Stellen, die sich ändern, schreiben Sie **Platzhalter** wie `${datum}`. Das Portal füllt sie mit den Angaben zu Seminar, Schule und Person und gibt Ihnen fertige Word-Dateien. Es wird nichts gespeichert oder versendet.

Wo: Koordination › **Briefvorlagen** (Recht `letters.use`: Koordination, Geschäftsstelle, Vorstand). Hochladen, ändern und löschen dürfen nur Geschäftsstelle und Vorstand (`letters.manage`). Erzeugen: auf der Seite eines Seminars, Abschnitt **Schreiben**.

## Anleitung für Word (einfache Sprache)
1. Laden Sie unter Briefvorlagen die **Beispielvorlage** herunter und öffnen Sie sie in Word.
2. Fügen Sie Ihren Briefbogen ein: das Logo in die **Kopfzeile**, Anschrift und Kontakt in die **Fußzeile** (Doppelklick oben oder unten auf die Seite). Platzhalter dürfen auch in Kopf- und Fußzeile stehen.
3. Schreiben Sie dort, wo etwas Wechselndes hin soll, einen Platzhalter. Beispiel: Statt „Frau Muster“ schreiben Sie `${empfaenger_name}`. Alle Platzhalter stehen unter Briefvorlagen › **Platzhalter**.
4. Wichtig: **Tippen Sie jeden Platzhalter in einem Zug.** Nicht zwischendurch löschen, nicht die Rechtschreibkorrektur bestätigen, nicht in der Mitte die Schrift ändern. Wenn ein Platzhalter später nicht gefüllt wird, markieren Sie ihn, löschen Sie ihn und tippen Sie ihn neu. Sicherer: aus der Platzhalter-Seite kopieren und mit „Nur Text einfügen“ einsetzen.
5. Die Formatierung (Schriftart, Größe, fett, Farbe) stellen Sie auf dem Platzhalter ein; sie bleibt beim Ausfüllen erhalten.
6. Speichern Sie als **Word-Dokument (.docx)**, nicht als .doc oder .docm (Makros sind nicht erlaubt).
7. Im Portal: Briefvorlagen › **Neue Vorlage** › Name, „Für“ wählen, Datei hochladen. Das Portal zeigt die gefundenen Platzhalter. Unbekannte (meist Tippfehler) sind markiert.
8. Seminar öffnen › **Schreiben** › Vorlage und Empfänger wählen, Betreff und freien Text eingeben › **Schreiben erzeugen**. Sie bekommen eine Word-Datei (bei mehreren Personen eine ZIP-Datei mit je einer Datei). Öffnen Sie sie in Word, ändern Sie bei Bedarf noch etwas und drucken oder speichern Sie sie als PDF.

## Platzhalter
Gruppen: Allgemein (`datum`, `datum_lang`, `jahr`, `betreff`, `freitext`), Absender (`absender_name`, `absender_email`, `verein_name`, `verein_kurz`, `verein_vorsitz`, `verein_anschrift`, `verein_ort`), Empfänger (`empfaenger_name`, `empfaenger_vorname`, `empfaenger_nachname`, `empfaenger_anschrift`, `empfaenger_strasse`, `empfaenger_plz`, `empfaenger_ort`, `empfaenger_email`), Schule (`schule_name`, `schule_anschrift`, `ansprechperson`), Seminar (`seminar_titel`, `seminar_nr`, `seminar_art`, `seminar_termine`, `seminar_erster_termin`, `seminar_ort`, `seminar_teilnehmende`). Die Vereinsdaten stammen aus Koordination › Verträge (Vereinsdaten). Ein bekannter Platzhalter ohne Wert wird leer, ein unbekannter bleibt sichtbar stehen.

## Wenn-Abfragen (Textblöcke unter einer Bedingung)
Zwei Markierungen blenden einen Textblock ein oder aus. **Jede Markierung steht in Word allein in einem eigenen Absatz:**
```
${wenn_ansprechperson}
Ihre Ansprechperson: ${ansprechperson}
${/wenn_ansprechperson}

${ohne_ansprechperson}
Bitte melden Sie sich bei der Geschäftsstelle.
${/ohne_ansprechperson}
```
- `${wenn_NAME}` … `${/wenn_NAME}`: Der Inhalt bleibt nur, wenn der Platzhalter NAME einen Wert hat.
- `${ohne_NAME}` … `${/ohne_NAME}`: Der Inhalt bleibt nur, wenn NAME leer ist.
- NAME ist ein beliebiger Platzhalter, zum Beispiel `verguetung`, `seminar_frist`, `freitext`, `ansprechperson`.
- Für verschiedene Texte an Schulen und Personen: `${wenn_empfaenger_ist_schule}` und `${wenn_empfaenger_ist_person}`. Eine Vorlage genügt dann für beide Empfängerarten.
- Gleich benannte Blöcke dürfen mehrfach vorkommen. Blöcke zu unbekannten Namen bleiben sichtbar stehen (die Vorlagenverwaltung warnt davor). Nicht möglich sind Vergleiche („wenn gleich“) und „sonst“; dafür nehmen Sie ein `wenn_` und ein `ohne_` nacheinander.

## Technik
- Bibliothek `phpoffice/phpword` (TemplateProcessor, benötigt `ext-zip`, `ext-xml`, `ext-dom`, `ext-gd`).
- Dateien: privat unter `storage/app/private/letters/templates` (Festplatte `local`), höchstens 5 MB, nur `.docx` ohne Makros; Aufbau und Größe werden geprüft.
- Zeilenumbrüche in Werten (Anschrift, Termine, freier Text) werden zu Zeilenumbrüchen in Word, Sonderzeichen werden maskiert.
- Protokoll: „Briefvorlage geändert“ und „Schreiben erzeugt“ (Seminarnummer und Zahl, keine Namen, keine Inhalte).

## Noch nicht umgesetzt
- Schreiben ab der Seite einer Schule oder Person (heute nur am Seminar).
- Serienbriefe an Verteiler oder Mitglieder.
- Ablegen erzeugter Schreiben als Seminar-Dokument.
- PDF-Ausgabe (der Server kann Word nicht in PDF umwandeln; dafür öffnen Sie die Datei in Word).

Ausführliche Platzhalter-Tabelle mit Beispielwerten, Quellen und allen fertigen Wenn-Beispielen: Abschnitt „Alle Platzhalter mit Beispielen“ in `docs/78_WORD_VORLAGEN_ANLEITUNG.md` und im Portal unter Briefvorlagen › Platzhalter.
