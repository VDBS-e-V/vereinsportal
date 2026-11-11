# Datenschutz: Vorstandssitzungen, Gäste und Kalender

Entwurf für Verzeichnis der Verarbeitungstätigkeiten (Art. 30 DSGVO), Datenschutzerklärung und Datenschutz-Folgenabschätzung-Vorprüfung. **Nicht rechtlich geprüft**: Bitte vom Verein oder der Datenschutzbeauftragten gegenzeichnen lassen.

## Verarbeitungstätigkeit „Vorstandssitzungen“

| Punkt | Inhalt |
|---|---|
| Zweck | Planung und Dokumentation der Vorstandsarbeit: Termine, Tagesordnung, Anwesenheit, Protokoll, Anhänge; Einladung per E-Mail und Kalender |
| Betroffene | Vorstandsmitglieder; externe Gäste; Personen, die in Protokollen oder Anhängen genannt werden |
| Daten | Vorstand: Name (aus dem Konto), Anwesenheit/Entschuldigung. Gäste: Name, E-Mail-Adresse, optional Organisation. Sitzung: Titel, Zeit, Ort, Videolink, Tagesordnung, Protokoll, Anhänge |
| Rechtsgrundlage | Vorstand: Art. 6 Abs. 1 lit. b (Vereinsverhältnis) und lit. f DSGVO. Gäste: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Durchführung der Sitzung); Interessenabwägung: geringer Eingriff, Gäste erwarten die Einladung, Löschung nach 30 Tagen |
| Empfänger | Nur Konten mit Rolle Vorstand. Gäste sehen nichts im Portal. Mailversand über den E-Mail-Dienst des Portals (Auftragsverarbeiter: Hoster, siehe Datenschutzerklärung) |
| Drittland | Keine Übermittlung |
| Löschfristen | Gäste: **30 Tage nach Ende der Sitzung** automatisch (`board:prune-guests`, einstellbar `BOARD_GUEST_RETENTION_DAYS`), jederzeit manuell. Sitzung, Protokoll, Anhänge: als Vereinsunterlagen so lange wie nötig, Löschung durch den Vorstand möglich |
| Informationspflicht (Art. 14) | Gäste erhalten die Information in der ersten Einladung (Hinweis zum Datenschutz mit Link zur Datenschutzerklärung); Quelle: Eintrag durch den Vorstand |

## Technische und organisatorische Maßnahmen

- Zugriff nur mit Berechtigung `board.meetings` (nur Rolle Vorstand); Gäste, Anhänge und Protokolle nie für andere Rollen sichtbar.
- Anhänge privat gespeichert (nie öffentlich verlinkt), Dateityp und Inhalt geprüft, keine Bilder.
- Einladungen gehen **einzeln** an jede Person (kein gemeinsamer Empfängerkreis, keine Sichtbarkeit fremder Adressen).
- Gästemail enthält keinen Portallink; die Tagesordnung nur, wenn ausdrücklich angekreuzt.
- Einladungen per E-Mail sind abschaltbar (Standard: aus) und gehen nur auf Knopfdruck hinaus; Vorstandsmitglieder können sie im Konto abstellen.
- Das Portalprotokoll nennt bei Gästen und Einladungen nur Anzahlen, nie Namen oder Adressen.
- Kalender-Abo: geheimer Schlüssel in der Adresse, jederzeit erneuerbar; enthält nur Titel, Zeit, Ort, Videolink, nie Tagesordnung oder Protokoll. Der Abruf ist ohne Anmeldung möglich, weil Kalenderprogramme sich nicht anmelden; wer die Adresse kennt, sieht die Termine. Videolinks mit Zugangscode sollten deshalb ohne Passwort im Link angegeben werden.

## Hinweis für bestehende Installationen

Der Standardtext der Seite „Datenschutz“ enthält jetzt den Abschnitt **„Vorstandssitzungen und Gäste“**. Wurde die Seite schon gespeichert, gilt Ihr gespeicherter Text weiter: Bitte den Abschnitt unter Geschäftsstelle › Seiten › Datenschutz einfügen (Text siehe `PageKey::privacy()` oder die Seite `/datenschutz` einer frischen Installation) und prüfen. Die Fassung `portal.legal.privacy_version` betrifft die Einwilligungen der Mitglieder und bleibt unverändert, solange sich für Mitglieder nichts Wesentliches ändert.

## Beschlossene Standardwerte

- Aufbewahrung Gäste: 30 Tage nach der Sitzung.
- Einladungen: standardmäßig aus.
- Tagesordnung in Gästemails: standardmäßig nicht enthalten.

## Offene Punkte

- Rechtliche Prüfung der Interessenabwägung und des Textes der Datenschutzerklärung.
- Eintrag im Verzeichnis der Verarbeitungstätigkeiten des Vereins.
- Prüfen, ob Protokolle mit Namen Dritter länger oder kürzer aufbewahrt werden sollen.
