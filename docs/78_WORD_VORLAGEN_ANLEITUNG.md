# Anleitung: Briefvorlage in Word mit Platzhaltern

Für alle, die Briefe gestalten. Sie brauchen Word (365, 2019 oder 2016). Vorkenntnisse sind nicht nötig. Die Anleitung geht vom deutschen Word aus.

## 1. Der Grundgedanke
Sie gestalten einen **normalen Brief** in Word. An allen Stellen, die sich von Brief zu Brief ändern (Name, Anschrift, Datum, Seminar …), schreiben Sie statt des echten Textes einen **Platzhalter** in der Form `${name}`. Das Portal ersetzt jeden Platzhalter beim Erzeugen durch den echten Wert. Alles andere, Logo, Schrift, Abstände und feste Texte, bleibt genau so, wie Sie es gestaltet haben.

Beispiel in Ihrer Vorlage: `Sehr geehrte Damen und Herren der ${schule_name},`
Ergebnis im fertigen Brief: `Sehr geehrte Damen und Herren der Fritz-Karsen-Schule,`

## 2. Vorbereitung
1. Im Portal: Koordination › **Briefvorlagen** › **Beispielvorlage** herunterladen. Öffnen Sie sie in Word. Sie enthält schon die wichtigsten Platzhalter.
2. Öffnen Sie im Portal in einem zweiten Fenster die Seite **Platzhalter**. Dort finden Sie alle erlaubten Platzhalter mit Erklärung.
3. **Wichtig, einmalig: Autokorrektur anpassen.** Word „verbessert“ beim Tippen manches von selbst. Das stört bei Platzhaltern. Gehen Sie so vor: *Datei › Optionen › Dokumentprüfung › AutoKorrektur-Optionen › Register „AutoFormat während der Eingabe“*. Nehmen Sie dort den Haken bei „Gerade Anführungszeichen durch typografische ersetzen“ nicht heraus, aber lassen Sie die übrigen Haken wie sie sind. Für Platzhalter genügt die Regel unten (in einem Zug tippen). Wenn Sie ganz sicher gehen wollen: Register „AutoKorrektur“ › Haken bei „Während der Eingabe ersetzen“ entfernen.

## 3. Seite einrichten
1. Register **Layout › Format**: A4.
2. **Layout › Seitenränder › Benutzerdefinierte Seitenränder**. Für einen Geschäftsbrief nach DIN 5008 sind üblich: links 2,5 cm, rechts 2 cm, oben 4,5 cm (Platz für den Briefkopf), unten 2 cm. Passen Sie die Werte an Ihren Briefbogen an.

## 4. Briefbogen einbauen: Kopf- und Fußzeile
Der Briefbogen (Logo, Vereinsname, Anschrift, Kontakt) gehört in die **Kopfzeile** und **Fußzeile**. Dort stört er nicht beim Schreiben und steht auf jeder Seite.
1. **Doppelklicken** Sie oben auf die leere Fläche am Seitenrand. Die Kopfzeile öffnet sich.
2. **Logo einfügen:** Register *Einfügen › Bilder › Dieses Gerät* und wählen Sie die Logo-Datei. Klicken Sie das Bild an, wählen Sie *Bildformat › Zeilenumbruch › Vor den Text* oder *Quadrat*, und ziehen Sie es an die gewünschte Stelle.
3. **Kontaktdaten in die Fußzeile:** Doppelklick unten auf die Seite und Anschrift, Telefon, E-Mail, Bankverbindung eintippen. Sie sind fest und brauchen keinen Platzhalter. Wenn die Anschrift des Vereins aus dem Portal kommen soll, schreiben Sie `${verein_anschrift}`.
4. **Erste Seite anders als Folgeseiten:** In der geöffneten Kopfzeile *Kopf- und Fußzeile › Optionen › Erste Seite anders*. Dann gestalten Sie die erste Seite mit großem Briefkopf und die folgenden mit einer schlichten Zeile.
5. Schließen mit *Kopf- und Fußzeile schließen* (oder Doppelklick in den Brieftext).

Platzhalter dürfen auch in Kopf- und Fußzeile stehen.

## 5. Platzhalter schreiben (die wichtigste Regel)
**Tippen Sie jeden Platzhalter in einem Zug von `${` bis `}`.**
- Nicht zwischendurch mit der Maus woanders hinklicken.
- Nicht zwischendurch löschen und neu anfangen mitten im Platzhalter.
- Nicht in der Mitte die Schriftart, Größe oder Farbe ändern.
- Die Rechtschreibprüfung darf den Platzhalter rot unterstreichen. Das ist egal, **klicken Sie nicht auf einen Korrekturvorschlag**.
- Groß- und Kleinschreibung beachten: alles klein, mit Unterstrich, zum Beispiel `${empfaenger_name}`, nicht `${Empfänger Name}`. Keine Umlaute, keine Leerzeichen.

Am sichersten: Platzhalter von der Platzhalter-Seite des Portals **kopieren** und in Word mit *Rechtsklick › Einfügen: Nur Text übernehmen* einsetzen.

Formatieren dürfen Sie den fertigen Platzhalter danach: markieren Sie ihn **ganz** (von `$` bis `}`) und machen Sie ihn fett, größer oder blau. Das Portal übernimmt diese Formatierung für den eingesetzten Wert.

## 6. Ein Beispielbrief Schritt für Schritt
So könnte eine Bestätigung an eine Schule aussehen (alles Fettgedruckte sind Platzhalter):

```
VDBS e. V. · ${verein_anschrift}          (kleine Absenderzeile über dem Adressfeld)

${empfaenger_name}
${empfaenger_anschrift}

${verein_ort}, ${datum}

${betreff}

Sehr geehrte Damen und Herren,

${freitext}

Seminar: ${seminar_titel} (${seminar_nr})
Termine:
${seminar_termine}

Mit freundlichen Grüßen
${absender_name}
```
So bauen Sie ihn:
1. Schreiben Sie die Zeilen wie oben. Der Platzhalter `${empfaenger_anschrift}` steht allein in einem Absatz: Das Portal macht daraus mehrere Zeilen (Straße, PLZ Ort). Das Gleiche gilt für `${seminar_termine}` (ein Termin pro Zeile) und `${freitext}` (so viele Zeilen, wie Sie beim Erzeugen eintippen).
2. Stellen Sie Schriftart und Größe (z. B. Arial 11) für den ganzen Brief ein: *Start › Schriftart*. Die Absatzabstände stellen Sie über *Start › Absatz* ein.
3. Den Betreff machen Sie fett: markieren Sie `${betreff}` komplett und drücken Sie Strg+F.

## 7. Weitere Hinweise zur Gestaltung
- **Tabellen** sind erlaubt, Platzhalter dürfen in Tabellenzellen stehen.
- **Textfelder und Formen** funktionieren meistens. Wenn ein Platzhalter darin nicht gefüllt wird, schreiben Sie ihn stattdessen in normalen Text.
- **Nicht verwenden:** Serienbrief-Felder von Word, Inhaltssteuerelemente (Entwicklertools), Makros, Formularfelder. Das Portal erkennt nur Platzhalter in der Form `${…}`.
- **Datum:** `${datum}` liefert 11.11.2026, `${datum_lang}` liefert „11. November 2026“.
- **Mehrere Ausfertigungen:** Wenn Sie für Schulen und Teamende verschiedene Briefe brauchen, legen Sie je eine eigene Datei an. Sie wählen beim Erzeugen die passende.
- **Schriftarten:** Verwenden Sie Schriften, die auf den Computern der Empfänger vorhanden sind (z. B. Arial, Calibri). Für Dokumente, die nur Sie ausdrucken, ist jede Schrift in Ordnung.

## 8. Speichern und hochladen
1. *Datei › Speichern unter* › Dateityp **Word-Dokument (*.docx)**. Nicht „Word 97–2003“ (.doc) und nicht „Word-Dokument mit Makros“ (.docm).
2. Im Portal: Briefvorlagen › **Neue Vorlage**. Geben Sie einen Namen ein (z. B. „Bestätigung an die Schule“), wählen Sie „Für“ (Schulen, Teamende oder Allgemein) und laden Sie die Datei hoch.
3. Nach dem Speichern zeigt das Portal die **gefundenen Platzhalter**. Steht dort eine gelbe Warnung „Unbekannte Platzhalter“, ist einer falsch geschrieben oder von Word zerteilt worden.

## 9. Ausprobieren
1. Öffnen Sie im Portal ein Seminar (am besten mit Testdaten) › Abschnitt **Schreiben**.
2. Wählen Sie Ihre Vorlage, den Empfänger, geben Sie Betreff und freien Text ein › **Schreiben erzeugen**.
3. Die Word-Datei öffnen Sie wie jede andere. Prüfen Sie: Stehen noch Platzhalter wie `${…}` im Text? Stimmen Anschrift und Datum? Sind Logo und Fußzeile da? Ändern Sie bei Bedarf noch etwas von Hand und drucken Sie oder speichern Sie als PDF (*Datei › Exportieren › PDF*).

## 10. Wenn etwas nicht klappt
| Beobachtung | Ursache und Lösung |
|---|---|
| Im fertigen Brief steht noch `${datum}` | Das Portal hat den Platzhalter nicht erkannt. Meist hat Word ihn beim Tippen zerteilt. Markieren Sie ihn komplett, löschen Sie ihn und tippen Sie ihn in einem Zug neu (oder kopieren Sie ihn aus dem Portal und fügen ihn als „Nur Text“ ein). Dann die Vorlage neu hochladen. |
| Im Portal steht „Unbekannte Platzhalter“ | Schreibweise prüfen: klein, Unterstrich, keine Umlaute. Vergleichen Sie mit der Liste auf der Platzhalter-Seite. |
| „Das ist keine gültige Word-Datei“ | Als .docx speichern (nicht .doc oder .docm, nicht per Umbenennen). |
| Ein Wert erscheint leer | Für diesen Empfänger gibt es keinen Wert (zum Beispiel keine Anschrift im Profil). Das Portal lässt die Stelle leer. |
| Logo oder Fußzeile fehlen im fertigen Brief | Sie stehen nicht in der Kopf-/Fußzeile, sondern im Brieftext, oder sind auf der falschen Seite („Erste Seite anders“). Prüfen Sie die Vorlage. |
| Datei zu groß | Höchstens 5 MB. Verkleinern Sie das Logo: Bild anklicken › *Bildformat › Bilder komprimieren*. |

## 11. Checkliste vor dem Hochladen
- [ ] Format A4, Ränder eingestellt.
- [ ] Logo in der Kopfzeile, Kontakt in der Fußzeile.
- [ ] Alle wechselnden Stellen sind Platzhalter, in einem Zug getippt.
- [ ] Anschrift, Termine und freier Text stehen jeweils allein in einem Absatz.
- [ ] Als .docx gespeichert, unter 5 MB.
- [ ] Nach dem Hochladen: keine Warnung „Unbekannte Platzhalter“.
- [ ] Einmal erzeugt und kontrolliert.
