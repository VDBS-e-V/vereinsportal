# Prüfliste Seminarablauf (Test im Browser)

Zum Abhaken beim Durchspielen des neuen Seminarablaufs (Beschreibung: Doc 89). Vor Beginn bitte alle Punkte unter „Vorbereitung“ erledigen. Bei jedem Fehler: Seite, Konto, was Sie getan haben und die Meldung notieren.

## Vorbereitung

- [ ] Stand holen: `git pull`, `composer install`, `php artisan migrate`, `npm run build`, `php artisan storage:link`
- [ ] In der `.env`: `MAIL_MAILER=log`, danach `php artisan config:clear`. Alle Mails stehen dann in `storage\logs\laravel.log` (Betreff, Empfänger, Text).
- [ ] Konten anlegen (Personen & Zugang › Benutzer, Einladungslink steht im Log): Administration, Geschäftsstelle, Vorstand, Koordination, Buchhaltung, drei Teamende (Anna, Ben, Clara; Rolle Teamende, Einarbeitung abgeschlossen)
- [ ] Verwaltung › Koordinationen: Koordination „Mitte“ anlegen; dem Konto der Koordination zuordnen, falls das Portal es verlangt
- [ ] Koordination › Schulen: Schule „Schule Eins“ anlegen, Koordination „Mitte“ wählen (Dropdown), Ansprechperson mit E-Mail eintragen, Schulkonto einladen und Einladung annehmen
- [ ] Geschäftsstelle › Seminare › Preisliste: mindestens zwei Leistungen mit Preis und einen Textbaustein anlegen
- [ ] Ein Konto ohne Seminarrechte (Vereinsmitglied) bereithalten

## 1. Anfrage

**Schule**
- [ ] Schulkonto: Bereich Schulen › Seminare › „Seminar anfragen“; ohne Thema absenden → verständliche Fehlermeldung
- [ ] Mit Thema, Format, Teilnehmerzahl, Zeitraum, Anmerkung absenden → Seminar erscheint in der Liste mit Nummer (S-JJJJ-NNNN) und Status „Anfrage“
- [ ] Log: Bestätigung an die Schule („Ihre Seminaranfrage …“) und Hinweis „Neue Seminaranfrage“ an die Geschäftsstelle

**Eintragen für die Schule**
- [ ] Koordination: Menü „Anfrage eintragen“ (auch Knopf in der Seminarliste); Schule per Dropdown wählen, Ansprechperson leer lassen → wird aus der Schule übernommen
- [ ] Schule ohne Ansprechperson und ohne Eingabe → Fehlermeldung
- [ ] Nach dem Eintragen: Hinweis, **keine** Bestätigung an die Schule im Log, Hinweis an die Geschäftsstelle ist da
- [ ] Koordination: in der Seminarliste Abschnitt „Anfragen in Prüfung“ sichtbar, ohne Link
- [ ] Geschäftsstelle und Vorstand können ebenfalls eintragen; Vereinsmitglied und Teamende erhalten 403
- [ ] Verlauf des Seminars zeigt „Im Auftrag der Schule eingetragen“

## 2. Prüfung und Kostenvoranschlag (KVA)

- [ ] Geschäftsstelle: Seminarliste (Suche, Filter Status, Koordination, Schuljahr) findet die Anfrage; Seminarseite öffnen
- [ ] Anfrage in Prüfung nehmen; Verlauf und Status stimmen
- [ ] KVA anlegen: Positionen aus der Preisliste, eigene Position, Textbaustein, Summen stimmen
- [ ] KVA senden → ohne interne Freigabe nicht möglich (Hinweis)
- [ ] Vorstand oder Koordination: Menü „KVA-Freigabe“ (Vorstand über `/kva-freigabe`), Freigabe erteilen
- [ ] KVA senden → Mail mit PDF an die Schule im Log; PDF-Anhang öffnen lässt sich prüfen (Version, Beträge)
- [ ] Schule: KVA im Seminar sehen; „Rückfrage“ senden → Hinweis an die Geschäftsstelle; „Ablehnen“ und „Annehmen“ jeweils mit Hinweis an die Geschäftsstelle
- [ ] Neue KVA-Version nach Rückfrage; die alte bleibt als Version erhalten
- [ ] Anfrage ablehnen (Begründung Pflicht) → Mail mit Begründung an die Schule; Seminar ist „Abgelehnt“; wieder in Prüfung nehmen geht

## 3. Freigabe

- [ ] Schulvertrag hochladen (PDF) mit Unterschriftsdatum: durch die Schule und, getrennt getestet, durch die Geschäftsstelle; Datum in der Zukunft wird abgelehnt
- [ ] Freigeben mit Vertrag → Status „Freigegeben“, Hinweis an die Koordination im Log
- [ ] Zweites Seminar: Freigabe auf verbindliche Zusage ohne Vertrag (Begründung Pflicht)
- [ ] Koordination sieht das freigegebene Seminar jetzt in „Seminare planen“

## 4. Planung und Ausschreibung

- [ ] Koordination › Seminare: Seminar öffnen; mehrere Termine mit Ort, Sollzahl Team, Bewerbungsfrist eintragen; Termin in der Vergangenheit wird abgelehnt
- [ ] Schuljahr wird aus dem ersten Termin gesetzt
- [ ] Ausschreiben → Hinweis an Teamende im Log (nur an die, die sich noch nicht gemeldet haben)
- [ ] Seminar zurück auf „Geplant“ nur mit Begründung

## 5. Bewerbung und Teamauswahl

- [ ] Teamende (Anna, Ben, Clara): Teamende › Seminare, Interesse melden, einmal zurückziehen und erneut melden
- [ ] Vereinsmitglied sieht die Seite nicht (403)
- [ ] Koordination: Bewerbungen sehen, Entwurf auswählen (Anna, Ben bestätigt; Clara Warteliste; ein vierter abgelehnt), Seminarleitung bestimmen
- [ ] Sollzahl überschreiten → Hinweis oder Sperre
- [ ] Bis „Team bestätigen“ geht keine Mail raus; danach gleichzeitig Zusage (Leitung mit Zusatz), Absage, Warteliste (Platz 1) im Log
- [ ] Ausscheiden einer Person und Nachrücken der Warteliste; neue Zusage geht raus
- [ ] Status „Team bestätigt“

## 6. Vorbereitung

- [ ] Onboarding-Termin eintragen und senden → Einladung an das bestätigte Team im Log (mit Link, wenn angegeben)
- [ ] Briefing an die Schule (Ablauf, Raum, Material) senden → Mail im Log
- [ ] Datei zum Seminar hochladen (PDF); Zugriff: Koordination, Seminarleitung, Geschäftsstelle ja, andere nein
- [ ] **Druckplaner**: Dokumente mit Exemplaren und Seiten pro Blatt anlegen, einmal Farbe und einmal Schwarzweiß; „PDF erzeugen“ → eine PDF mit Deckblatt, erst Schwarzweiß, dann Farbe, Seitenzahl stimmt
- [ ] PDF 1.5 oder neuer hochladen → erscheint als „nicht im Druckplaner“ mit Hinweis
- [ ] Seminarmappe als ZIP laden und öffnen
- [ ] Nextcloud (nur wenn eingerichtet): Ordner wird angelegt, Ergebnislink erscheint; ohne Zugangsdaten bleibt der Handweg (Link von Hand eintragen)
- [ ] Als „Vorbereitet“ markieren

## 7. Durchführung und Bericht

- [ ] Seminarleitung: Teamende › Meine Leitung zeigt nur das eigene Seminar; andere Teamende sehen es nicht
- [ ] „Durchgeführt“ bestätigen
- [ ] Bericht (PDF) und Teamverträge hochladen; je Vertrag Betrag und Person eintragen
- [ ] Je Vertrag entsteht ein Zahlungsauftrag; Hinweis an die Buchhaltung im Log
- [ ] Bericht abgeben → Status „Berichtet“
- [ ] Koordination: Bericht zurückgeben (Begründung Pflicht) und erneut abgeben
- [ ] Ergebnisse an die Schule senden → Mail mit Ergebnislink, Ablaufdatum und Bitte um Rückmeldung
- [ ] Ergebnislink verlängern
- [ ] Seminar abschließen → Status „Abgeschlossen“

## 8. Zahlungsaufträge (Buchhaltung)

- [ ] Geschäftsstelle › Zahlungsaufträge: nur Konten mit dem Recht sehen die Seite
- [ ] Auftrag bezahlen, einen ablehnen (Begründung); Status und Verlauf stimmen
- [ ] Export (Sammelliste) lässt sich laden; Beträge und Namen stimmen
- [ ] Bankdaten stehen nur im hochgeladenen Vertrag, nicht in der Oberfläche

## 9. Rückmeldungen

- [ ] Schule: Rückmeldung zum Seminar abgeben (Bewertungen und Text); zweites Mal nicht möglich
- [ ] Teamende geben ihre Rückmeldung ab
- [ ] Koordination › Rückmeldungen: Auswertung zeigt Durchschnitte und Texte, Filter nach Koordination

## 10. Absagen, Neu ansetzen, Wiederöffnen

- [ ] Seminar absagen (Begründung Pflicht) → Mails an Schule und Team
- [ ] Neu ansetzen (zurück auf „Geplant“ oder „In Prüfung“) → Mail „Neuer Termin“, bei Zurücksetzen des Teams Mail an das Team
- [ ] Abgeschlossenes Seminar wieder öffnen: nur Vorstand, mit Begründung

## 11. Rechte und Sichtbarkeit

| Konto | Soll |
|---|---|
| Schule | nur eigene Seminare; fremde Seminar-Adresse → 404 |
| Vereinsmitglied | kein Zugriff auf Seminarseiten (403) |
| Teamende | Ausschreibungen, eigene Seminare; keine Planung |
| Koordination | Planung, Team, Bericht, KVA-Freigabe, Eintragen; keine Zahlungsaufträge |
| Geschäftsstelle | Prüfung, KVA, Vertrag, Freigabe, Zahlungsaufträge, Eintragen |
| Vorstand | KVA-Freigabe, Wiederöffnen, Eintragen |

- [ ] Jede Zeile mit dem passenden Konto durchgeklickt, Menüpunkte erscheinen nur für berechtigte Konten

## 12. Mitteilungen

- [ ] Konto › Benachrichtigungen: Seminaranfragen, Freigaben, Ausschreibungen, Hinweise zu eigenen Seminaren, Berichtserinnerungen, Zahlungsaufträge lassen sich einzeln abschalten
- [ ] Nach dem Abschalten kommt die jeweilige Mail nicht mehr; Zusage, Absage, Warteliste, KVA und Mails an die Schule bleiben Pflicht
- [ ] Geschäftsstelle › Systemmails › Gruppe „Seminare“: Text eines Anlasses ändern (z. B. „Seminar: Absage (Teamende)“), Testmail an sich senden, danach den Anlass im Ablauf auslösen → neuer Text im Log; „Auf Standard zurücksetzen“ stellt den alten wieder her

## 13. Geplante Läufe

- [ ] System › Aufgaben: `seminars:remind-deadlines`, `seminars:remind-reports`, `seminars:results-expiry`, `seminars:prune-requests` einzeln von Hand starten; Meldung ohne Fehler
- [ ] Bewerbungsfrist in zwei Tagen → Erinnerung an Teamende, die sich nicht gemeldet haben
- [ ] Stichtag Bericht überschritten → Hinweis an die Seminarleitung und die Koordination

## 14. Querschnitt

- [ ] Jede Seite per Tastatur bedienbar, Fokus sichtbar; Fehlermeldungen stehen am Feld
- [ ] Handy-Breite (Browser auf 390 px): Listen und Formulare nutzbar, kein seitliches Scrollen
- [ ] Dunkelmodus: Seiten lesbar
- [ ] Verlauf eines Seminars vollständig (jeder Statuswechsel mit Person und Zeit); Protokoll (Admin › Audit) enthält die Seminar-Ereignisse
- [ ] Datei-Download fremder Seminare ist nicht möglich (Adresse eines Dokuments mit fremdem Konto öffnen → 403 oder 404)

## Ergebnis

Notieren Sie je Fehler: Abschnitt, Konto, Schritte, erwartet, tatsächlich. Alles, was hier fehlschlägt, gilt es zu beheben, bevor der Ablauf produktiv genutzt wird.
