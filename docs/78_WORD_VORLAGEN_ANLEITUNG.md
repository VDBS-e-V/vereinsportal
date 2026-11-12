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

## 7a. Wenn-Abfragen: Text nur unter einer Bedingung
Manche Sätze sollen nur erscheinen, wenn es etwas dazu gibt, zum Beispiel ein Hinweis auf die Ansprechperson oder das Honorar. Dafür gibt es **Blöcke mit Anfang und Ende**:
```
${wenn_ansprechperson}
Ihre Ansprechperson: ${ansprechperson}
${/wenn_ansprechperson}
```
Regeln:
1. **Anfang und Ende stehen jeweils allein in einem eigenen Absatz** (nichts anderes in derselben Zeile), sonst wird der Block nicht erkannt.
2. Der Block erscheint nur, wenn der Platzhalter hinter `wenn_` einen Wert hat. `ohne_` kehrt das um: `${ohne_ansprechperson}` … `${/ohne_ansprechperson}` erscheint nur, wenn es **keinen** Wert gibt.
3. Anfang und Ende müssen genau gleich heißen, das Ende hat zusätzlich den Schrägstrich `/`.
4. Eine Vorlage für Schulen und Personen: `${wenn_empfaenger_ist_schule}` zeigt Text nur bei Schreiben an Schulen, `${wenn_empfaenger_ist_person}` nur bei Schreiben an Personen.
5. Die Leerzeilen um den Block bleiben stehen. Setzen Sie sie in den Block hinein, wenn sie mit verschwinden sollen.
6. Ein „sonst“ gibt es nicht. Schreiben Sie zwei Blöcke hintereinander: einen mit `wenn_` und einen mit `ohne_`.

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
| Ein Block erscheint nicht oder steht noch mit `${wenn_…}` im Brief | Anfang und Ende stehen nicht allein im Absatz, heißen nicht gleich, oder das Ende hat keinen `/`. Prüfen Sie die Schreibweise und setzen Sie beide Markierungen in eigene Zeilen. |
| Datei zu groß | Höchstens 5 MB. Verkleinern Sie das Logo: Bild anklicken › *Bildformat › Bilder komprimieren*. |

## 11. Checkliste vor dem Hochladen
- [ ] Format A4, Ränder eingestellt.
- [ ] Logo in der Kopfzeile, Kontakt in der Fußzeile.
- [ ] Alle wechselnden Stellen sind Platzhalter, in einem Zug getippt.
- [ ] Anschrift, Termine und freier Text stehen jeweils allein in einem Absatz.
- [ ] Als .docx gespeichert, unter 5 MB.
- [ ] Nach dem Hochladen: keine Warnung „Unbekannte Platzhalter“.
- [ ] Einmal erzeugt und kontrolliert.

### Alle Platzhalter mit Beispielen

**Allgemein** – Gelten für jedes Schreiben, unabhängig vom Empfänger.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${datum}` | Heutiges Datum | 11.11.2026 | Datum beim Erzeugen |
| `${datum_lang}` | Heutiges Datum ausgeschrieben | 11. November 2026 | Datum beim Erzeugen |
| `${jahr}` | Aktuelles Jahr | 2026 | Datum beim Erzeugen |
| `${betreff}` | Betreff des Schreibens | Bestätigung Ihres Seminars | Feld „Betreff“ beim Erzeugen – Leer, wenn beim Erzeugen nichts eingetragen wird. Mit ${wenn_betreff} lässt sich eine Betreffzeile ausblenden. |
| `${freitext}` | Freier Text, mehrere Zeilen möglich | Wir freuen uns auf Sie. | Feld „Freier Text“ beim Erzeugen – Zeilenumbrüche bleiben erhalten. Steht am besten allein in einem Absatz. |

**Absender** – Die Person, die das Schreiben im Portal erzeugt, und der Verein.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${absender_name}` | Name der erzeugenden Person | Karla Koordina | Konto der angemeldeten Person (Stammdaten) |
| `${absender_email}` | E-Mail-Adresse dieser Person | karla@vdb.schule | Konto der angemeldeten Person |
| `${absender_telefon}` | Telefonnummer dieser Person | 030 123456 | Stammdaten der Person – Leer, wenn keine Nummer gespeichert ist. |
| `${verein_name}` | Name des Vereins, ausgeschrieben | Verband für Demokratiebildung und Bibliotheken an Schulen e.V. | Koordination › Verträge › Vereinsdaten |
| `${verein_kurz}` | Kurzname des Vereins | VDBS e.V. | Vereinsdaten |
| `${verein_vorsitz}` | Vorsitz | Jan Ole Schmiedecke | Vereinsdaten |
| `${verein_anschrift}` | Anschrift des Vereins | Fritz-Karsen-Schule, Onkel-Bräsig-Straße 76, 12359 Berlin | Vereinsdaten |
| `${verein_ort}` | Ort des Vereins | Berlin | Vereinsdaten (Ort für die Unterschrift) – Für Zeilen wie „Berlin, 11.11.2026“. |

**Empfänger (Schule oder Person)** – Bei „An die Schule“ stehen hier die Angaben der Schule, bei „An Personen aus dem Team“ die der jeweiligen Person. So funktioniert eine Vorlage für beide.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${empfaenger_name}` | Vollständiger Name der Person oder Name der Schule | Anna Alt / Gesamtschule am Park | Person: Profil oder Kontakt; Schule: Stammdaten |
| `${empfaenger_vorname}` | Vorname | Anna | Person: Profil oder Kontakt – Bei Schulen leer. |
| `${empfaenger_nachname}` | Nachname | Alt | Person: Profil oder Kontakt – Bei Schulen leer. |
| `${empfaenger_anschrift}` | Anschrift in mehreren Zeilen (Straße, dann PLZ und Ort) | Musterweg 5 / 10115 Berlin | Person: Profil oder Kontakt; Schule: Stammdaten – Steht am besten allein in einem Absatz. Leer, wenn keine Anschrift bekannt ist. |
| `${empfaenger_strasse}` | Straße und Hausnummer | Musterweg 5 | wie Anschrift |
| `${empfaenger_plz}` | Postleitzahl | 10115 | wie Anschrift |
| `${empfaenger_ort}` | Ort | Berlin | wie Anschrift |
| `${empfaenger_email}` | E-Mail-Adresse | anna@beispiel.example | Person: Konto oder Kontakt; Schule: Hauptansprechperson oder Schuladresse |
| `${empfaenger_telefon}` | Telefonnummer | 030 987654 | Person: Profil oder Kontakt; Schule: Ansprechperson oder Schule – Oft leer. |

**Schule des Seminars** – Stehen in allen Schreiben zum Seminar zur Verfügung, auch bei Schreiben an Personen aus dem Team.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${schule_name}` | Name der Schule | Gesamtschule am Park | Schule des Seminars (Schulen) |
| `${schule_anschrift}` | Anschrift der Schule in mehreren Zeilen | Schulweg 3 / 12345 Berlin | Schule des Seminars |
| `${schule_telefon}` | Telefonnummer der Schule | 030 123456 | Schule des Seminars – Oft leer. |
| `${schule_email}` | E-Mail-Adresse der Schule | sekretariat@schule.example | Schule des Seminars – Oft leer. |
| `${schule_nummer}` | Interne Kennung der Schule | BE/08K06 | Bundesland und Schulnummer – Leer, solange eins von beiden fehlt. |
| `${schule_bundesland}` | Bundesland der Schule | Berlin | Schule des Seminars |
| `${schule_schulart}` | Schulart | Gymnasium | Schule des Seminars |
| `${ansprechperson}` | Name der Hauptansprechperson | Frau Lehmann | Ansprechpersonen der Schule (Hauptperson zuerst) – Leer, wenn die Schule keine Ansprechperson hat. Ideal für Wenn-Blöcke. |
| `${ansprechperson_email}` | E-Mail-Adresse der Hauptansprechperson | lehmann@schule.example | Ansprechpersonen der Schule |
| `${ansprechperson_telefon}` | Telefonnummer der Hauptansprechperson | 030 555555 | Ansprechpersonen der Schule |

**Seminar** – Angaben des Seminars, zu dem Sie das Schreiben erzeugen.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${seminar_titel}` | Titel des Seminars | SV-Tage Nord | Seminar |
| `${seminar_nr}` | Seminarnummer | S-2027-0042 | Seminar |
| `${seminar_art}` | Art des Seminars | SV-Tage | Seminar |
| `${seminar_termine}` | Alle Termine, jeder in einer eigenen Zeile | 12.05.2027, 09:00–15:00 Uhr – Aula / 13.05.2027, 09:00–15:00 Uhr – Aula | Termine des Seminars – Steht am besten allein in einem Absatz. |
| `${seminar_erster_termin}` | Erster Termin | 12.05.2027, 09:00–15:00 Uhr | Termine des Seminars |
| `${seminar_beginn}` | Datum des ersten Termins | 12.05.2027 | Termine des Seminars |
| `${seminar_ende}` | Datum des letzten Termins | 13.05.2027 | Termine des Seminars |
| `${seminar_tage}` | Zahl der Tage mit Terminen | 2 | Termine des Seminars |
| `${seminar_stunden}` | Gesamtdauer aller Termine in Stunden | 12 | Termine des Seminars – Mit Komma bei halben Stunden, z. B. 4,5. |
| `${seminar_ort}` | Ort des ersten Termins | Aula | Termine des Seminars – Leer, wenn der Termin keinen Ort hat. |
| `${seminar_teilnehmende}` | Erwartete Zahl der Teilnehmenden | 40 | Seminar – Leer, wenn nicht eingetragen. |
| `${seminar_team}` | Namen der bestätigten Teamenden, durch Komma getrennt | Anna Alt, Ben Bunt | Bestätigtes Team des Seminars – Leer, solange niemand bestätigt ist. |
| `${seminar_koordination}` | Verantwortliche Koordination | Karla Koordina | Seminar (Feld „Verantwortliche Koordination“) – Leer, wenn nicht festgelegt. |
| `${seminar_frist}` | Stichtag für Interessenbekundungen | 30.04.2027 | Seminar – Leer, wenn kein Stichtag gesetzt ist. |
| `${verguetung}` | Honorar je Person laut Kostenvoranschlag | 150,00 € | Kostenvoranschlag des Seminars – Ohne Zuschlag für die Seminarleitung. Leer, wenn es keinen Kostenvoranschlag gibt. |

**Bedingungen (nur für Wenn-Blöcke)** – Diese beiden Platzhalter sind nur zum Ein- und Ausblenden gedacht, siehe Abschnitt „Wenn-Abfragen“.

| Platzhalter | Bedeutung | Beispiel | Quelle / Hinweis |
|---|---|---|---|
| `${empfaenger_ist_schule}` | Gefüllt, wenn das Schreiben an die Schule geht | 1 (sonst leer) | Wahl „Empfänger“ beim Erzeugen |
| `${empfaenger_ist_person}` | Gefüllt, wenn das Schreiben an eine Person aus dem Team geht | 1 (sonst leer) | Wahl „Empfänger“ beim Erzeugen |

### Fertige Wenn-Beispiele

**Ansprechperson nennen, wenn es eine gibt** – Nur anzeigen, wenn die Schule eine Ansprechperson hat.

```
${wenn_ansprechperson}
Ihre Ansprechperson: ${ansprechperson} (${ansprechperson_email})
${/wenn_ansprechperson}
```

**Ersatztext, wenn es keine gibt** – Das Gegenstück: erscheint nur ohne Ansprechperson.

```
${ohne_ansprechperson}
Bitte wenden Sie sich an unsere Geschäftsstelle.
${/ohne_ansprechperson}
```

**Unterschiedlicher Text für Schulen und Personen** – Eine Vorlage für beide Empfängerarten.

```
${wenn_empfaenger_ist_schule}
wir bestätigen Ihnen das Seminar an Ihrer Schule.
${/wenn_empfaenger_ist_schule}
${wenn_empfaenger_ist_person}
vielen Dank für Ihre Mitarbeit beim Seminar.
${/wenn_empfaenger_ist_person}
```

**Honorar nur nennen, wenn bekannt** – Für Schreiben an Teamende.

```
${wenn_verguetung}
Ihr Honorar beträgt ${verguetung}.
${/wenn_verguetung}
```

**Hinweis zum Stichtag** – Nur, wenn für das Seminar ein Stichtag gesetzt ist.

```
${wenn_seminar_frist}
Bitte melden Sie sich bis zum ${seminar_frist}.
${/wenn_seminar_frist}
```

**Freitext nur bei Eingabe** – Absatz erscheint nur, wenn beim Erzeugen ein freier Text eingegeben wurde.

```
${wenn_freitext}
${freitext}
${/wenn_freitext}
```

**Betreffzeile nur mit Betreff** – Blendet die ganze Betreffzeile aus, wenn kein Betreff eingegeben wurde.

```
${wenn_betreff}
Betreff: ${betreff}
${/wenn_betreff}
```

**Team nennen, wenn schon bestätigt** – Für Schreiben an die Schule.

```
${wenn_seminar_team}
Ihr Team: ${seminar_team}
${/wenn_seminar_team}
${ohne_seminar_team}
Das Team wird noch zusammengestellt.
${/ohne_seminar_team}
```

**Zwei Bedingungen nacheinander (sonst-Ersatz)** – Ein „sonst“ gibt es nicht: Nehmen Sie einen wenn- und einen ohne-Block hintereinander.

```
${wenn_schule_telefon}
Telefon: ${schule_telefon}
${/wenn_schule_telefon}
${ohne_schule_telefon}
Telefon: nicht bekannt
${/ohne_schule_telefon}
```

**Verschachtelt: Ansprechperson und Telefon** – Ein Block in einem anderen, mit verschiedenen Namen.

```
${wenn_ansprechperson}
Ansprechperson: ${ansprechperson}
${wenn_ansprechperson_telefon}
Telefon: ${ansprechperson_telefon}
${/wenn_ansprechperson_telefon}
${/wenn_ansprechperson}
```

