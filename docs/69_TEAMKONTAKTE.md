# 69 Teamkontakte und persönlicher Zugang (Paket C, Teil 1)

Stand: Teil 1 von Paket C (C0). **Noch nicht auf dem echten System erprobt.** Die Bewerbung selbst (ohne Konto) folgt in Teil 2.

## Was es gibt
- **Import der Mailingliste** (Geschäftsstelle › Personen & Zugang › Teamkontakte › Liste importieren): CSV-Datei, Textdatei oder Einfügen aus Excel. Es genügen E-Mail-Adressen; Vorname, Nachname und Telefon werden übernommen, wenn die Liste sie enthält. Zuerst eine Vorschau (nichts wird gespeichert), danach der Import, alles oder nichts. Vorhandene Daten werden nie überschrieben, nur leere Felder ergänzt. Das Häkchen „Die Einwilligung zur E-Mail-Kommunikation liegt vor“ ist Pflicht und wird mit Datum gespeichert. Im Protokoll stehen nur Zahlen.
- **Persönlicher Zugang je Adresse:** ein Link (`/z/<Token>`) und ein Code (`K7QM4-XWD2K`) zum Eintippen auf `/zugang` zusammen mit der E-Mail-Adresse. Beides steht nur im Moment des Versands im Klartext in der E-Mail; gespeichert sind Prüfsummen. Der Code ist gedrosselt (5 Fehlversuche je Adresse und Stunde). Die Antwort bei Fehlern ist immer dieselbe.
- **Einzeln hinzufügen:** „Adresse hinzufügen“ legt eine neue Person an (Einwilligung bestätigen, Zugang auf Wunsch sofort senden).
- **Mehrfachauswahl in der Liste:** Filter „Zugang“ mit mehreren Zuständen, Kästchen je Zeile und „alle auswählen“ im Tabellenkopf; Aktionen für die Auswahl: Zugang senden, sperren, löschen (mit Bestätigungsseite).
- **Versand:** je Adresse oder an alle, die noch keinen Zugang bekamen. Gesperrte und abgemeldete Adressen bekommen nichts. Die Mail trägt einen Abmeldelink (Thema „Mitteilungen an Teamende“) und die One-Click-Kopfzeilen. Der Versand läuft über die Warteschlange (Cron-Aufruf).
- **Neu senden / sperren / löschen** in der Liste. Ein neuer Zugang lässt frühere noch 14 Tage weiter gelten (`CONTACT_ACCESS_GRACE_DAYS`), Sperren wirkt sofort.
- **Zugang für Besucher ohne Konto:** `/zugang` (Anmelden, „Zugang verloren?“ schickt einen neuen), `/zugang/start` zeigt die offenen Seminare (nur lesen) und die eigenen Angaben. Name Pflicht, Telefon und Anschrift freiwillig. **Nur mit „Für die Zukunft speichern“** bleiben die Angaben im Kontakt, sonst gelten sie nur für den Besuch. Die Sitzung endet nach 30 Minuten ohne Aktivität (`CONTACT_ACCESS_IDLE_MINUTES`) und öffnet keinen Bereich des Portals.

## Berechtigung
`contacts.manage`: Geschäftsstelle und Vorstand.

## Noch offen (Teil 2)
Bewerben und Zurückziehen mit dem Zugang, Gäste in Bewerberliste und Teamvorschlag, Entscheidungsmails an Kontakte, allgemeiner Seminarlink als Zweitweg, „Team informieren“, Aufräumbefehl für Kontakte ohne Aktivität (Vorschlag nach 24 Monaten), Kontoaktivierung über den Zugang.

## Teil 2: Bewerbung mit dem persönlichen Zugang
- Auf `/zugang/start` sehen Kontakte die offenen Seminare. **Mehrere Seminare lassen sich anhaken** (auch „alle auswählen“) und mit einem Klick bewerben. Angaben: Kompetenzen (Mehrfachauswahl), Erfahrung, Geburtsjahr (freiwillig), Telefon, Nachricht, Wunsch zur Seminarleitung, Weitergabe der Kontaktdaten ans Team (freiwillig) und die Zustimmung zur Verarbeitung (Pflicht). Kompetenzen und Telefon bleiben nur mit „Für die Zukunft speichern“.
- **Ihre Bewerbungen:** Liste mit Stand; mehrere offene Bewerbungen lassen sich gemeinsam zurückziehen.
- **Koordination:** Bewerbungen über den Zugang erscheinen in der Liste der Seminarseite mit dem Kennzeichen „Persönlicher Zugang“, werden in Teamvorschlag, Zusammensetzung und Überschneidungswarnung berücksichtigt und lassen sich wie Konto-Bewerbungen bestätigen oder ablehnen. Entscheidungs- und Absagemails gehen an die Adresse des Kontakts (nicht an gesperrte oder abgemeldete), mit Link auf `/zugang`.
- **Mitteilungen an Teamende** mit Zielgruppe „Interesse bekundet“ oder „Im Team“ erreichen auch diese Bewerbenden.
- Löscht man einen Kontakt, verschwinden seine Bewerbungen mit.

## Noch offen (Teil 3)
Allgemeiner Seminarlink als Zweitweg mit E-Mail-Bestätigung, „Team informieren“ (gemeinsame Mail mit Team-Übersicht), Aufräumbefehl für Kontakte ohne Aktivität, Kontoaktivierung über den Zugang, Honorar-Überblick nach Anmeldung.

## Teil 3: Bewerbungslink, Team informieren, Aufbewahrung
- **Bewerbungslink je Seminar** (Seminarseite › Team › „Bewerbungslink erzeugen“): für Personen ohne persönlichen Zugang. Der Link wird nur beim Erzeugen angezeigt (gespeichert ist eine Prüfsumme) und gilt bis einen Tag nach dem Stichtag; ein neuer Link sperrt den alten, „Link sperren“ beendet ihn. Die öffentliche Seite zeigt Titel, Leistung, Ort (Stadt), Termin, Bedarf und Stichtag, **nicht** Schulname, Straße, Ansprechperson oder Honorar.
- **Bestätigung per E-Mail:** Die Bewerbung zählt erst nach dem Klick auf den signierten Link (24 Stunden). Bis dahin erscheint sie nicht in der Auswertung und lässt sich nicht entscheiden. Die Antwort nach dem Absenden ist immer dieselbe; wer die Mail nicht bekam, sendet das Formular erneut. Die Adresse wird als Kontakt mit Herkunft „Bewerbungslink“ angelegt und bekommt **keine** Massen-Zugangsmail.
- **Team informieren** (Seminarseite, sobald jemand im Team ist): eine Nachricht an die ausgewählten Teammitglieder (Mehrfachauswahl, alle vorgewählt, gesperrte und abgemeldete Adressen werden ausgelassen) mit Termine, Ort, eigenem Zusatztext und Team-Übersicht. Kontaktdaten stehen nur bei Personen, die der Weitergabe zugestimmt haben. Ändert sich das Team danach, weist die Seite darauf hin.
- **Aufbewahrung** (`php artisan seminars:prune-applicants`, täglich und als Systemaufgabe „Bewerbungen ohne Konto aufräumen“): unbestätigte Bewerbungen werden nach 7 Tagen gelöscht, zurückgezogene nach 30 Tagen anonymisiert, alle übrigen 6 Monate nach dem letzten Termin oder der Absage (`CONTACT_APPLICANT_RETENTION_MONTHS`). Kontakte, die nur über den Link kamen und keine Bewerbung mehr haben, werden gelöscht.

## Noch offen
Kontoaktivierung über den Zugang (kommt mit den Teamkonten), Honorar-Überblick nach der Anmeldung, QR-Code für den Bewerbungslink, Vorschlag zur Löschung von Kontakten ohne Nutzung nach 24 Monaten.
