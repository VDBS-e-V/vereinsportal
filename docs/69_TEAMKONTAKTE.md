# 69 Teamkontakte und persönlicher Zugang (Paket C, Teil 1)

Stand: Teil 1 von Paket C (C0). **Noch nicht auf dem echten System erprobt.** Die Bewerbung selbst (ohne Konto) folgt in Teil 2.

## Was es gibt
- **Import der Mailingliste** (Geschäftsstelle › Personen & Zugang › Teamkontakte › Liste importieren): CSV-Datei, Textdatei oder Einfügen aus Excel. Es genügen E-Mail-Adressen; Vorname, Nachname und Telefon werden übernommen, wenn die Liste sie enthält. Zuerst eine Vorschau (nichts wird gespeichert), danach der Import, alles oder nichts. Vorhandene Daten werden nie überschrieben, nur leere Felder ergänzt. Das Häkchen „Die Einwilligung zur E-Mail-Kommunikation liegt vor“ ist Pflicht und wird mit Datum gespeichert. Im Protokoll stehen nur Zahlen.
- **Persönlicher Zugang je Adresse:** ein Link (`/z/<Token>`) und ein Code (`K7QM4-XWD2K`) zum Eintippen auf `/zugang` zusammen mit der E-Mail-Adresse. Beides steht nur im Moment des Versands im Klartext in der E-Mail; gespeichert sind Prüfsummen. Der Code ist gedrosselt (5 Fehlversuche je Adresse und Stunde). Die Antwort bei Fehlern ist immer dieselbe.
- **Versand:** je Adresse oder an alle, die noch keinen Zugang bekamen. Gesperrte und abgemeldete Adressen bekommen nichts. Die Mail trägt einen Abmeldelink (Thema „Mitteilungen an Teamende“) und die One-Click-Kopfzeilen. Der Versand läuft über die Warteschlange (Cron-Aufruf).
- **Neu senden / sperren / löschen** in der Liste. Ein neuer Zugang lässt frühere noch 14 Tage weiter gelten (`CONTACT_ACCESS_GRACE_DAYS`), Sperren wirkt sofort.
- **Zugang für Besucher ohne Konto:** `/zugang` (Anmelden, „Zugang verloren?“ schickt einen neuen), `/zugang/start` zeigt die offenen Seminare (nur lesen) und die eigenen Angaben. Name Pflicht, Telefon und Anschrift freiwillig. **Nur mit „Für die Zukunft speichern“** bleiben die Angaben im Kontakt, sonst gelten sie nur für den Besuch. Die Sitzung endet nach 30 Minuten ohne Aktivität (`CONTACT_ACCESS_IDLE_MINUTES`) und öffnet keinen Bereich des Portals.

## Berechtigung
`contacts.manage`: Geschäftsstelle und Vorstand.

## Noch offen (Teil 2)
Bewerben und Zurückziehen mit dem Zugang, Gäste in Bewerberliste und Teamvorschlag, Entscheidungsmails an Kontakte, allgemeiner Seminarlink als Zweitweg, „Team informieren“, Aufräumbefehl für Kontakte ohne Aktivität (Vorschlag nach 24 Monaten), Kontoaktivierung über den Zugang.
