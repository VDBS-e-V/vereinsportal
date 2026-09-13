# Funktionale Beta-Abnahme

## Status

Die fachlichen Beta-Bausteine sind implementiert. Dieses Dokument ist das gemeinsame Abnahme-Gate vor Hosting und Staging.

Automatisierte Referenzbasis nach der letzten Aktualisierung des Beta-Branches:

- `main`: `56ea6cea19c607ea9236975fbb7577247e4e1362`
- CI: grün auf dem zugrunde liegenden `main`-Stand
- Security: grün auf dem zugrunde liegenden `main`-Stand
- Designsystem: technische Basis vollständig, manuelle QA aus #16 offen
- GitHub-Settings-Ausführung: `docs/25_GITHUB_SETTINGS_RUNBOOK.md`
- Design-/Accessibility-Ausführung: `docs/26_DESIGN_QA_EXECUTION.md`
- spätere Betriebsentscheidungen: `docs/27_OPERATIONS_DECISION_MATRIX.md`

Automatische Checks ersetzen die folgenden manuellen Produkt- und Browserprüfungen nicht. Nach Änderungen an diesem Branch müssen CI und Security erneut vollständig grün sein, bevor eine Beta-Freigabe überhaupt bewertet wird.

## 1. End-to-End-Hauptstrecke

Mit realistischen Testdaten einmal vollständig durchführen:

- [ ] Person in `/verwaltung/personen` finden oder neu anlegen
- [ ] Personendaten bearbeiten und Änderung erneut öffnen
- [ ] als berechtigter Vorstand eine Mitgliedschaft anlegen
- [ ] Mitgliedschaft bearbeiten und Verlauf prüfen
- [ ] falls für den Test sinnvoll: Dokument/Zustimmungsnachweis an der Mitgliedschaft prüfen
- [ ] für eine Person ohne Konto eine Portal-Einladung versenden
- [ ] Einladung annehmen und bestehende Person mit dem neuen Benutzerkonto verknüpfen
- [ ] Login mit dem neu entstandenen Konto durchführen
- [ ] Passwort-/Kontofunktionen stichprobenartig prüfen
- [ ] 2FA aktivieren und einen Login mit 2FA durchführen
- [ ] E-Mail-Zustellung bzw. Delivery-Status der Einladung nachvollziehen
- [ ] relevante Schreibvorgänge im Audit wiederfinden

Erwartung: Für keinen dieser Schritte ist ein Datenbank- oder CLI-Eingriff nötig.

## 2. Berechtigungsgrenzen

Mindestens diese Rollenprofile manuell prüfen:

### Nur `administration`

- [ ] `/verwaltung` erreichbar
- [ ] Personen- und Benutzerverwaltung erreichbar
- [ ] `/vorstand` nicht erreichbar
- [ ] keine Mitgliedschaftsdaten auf Personenansichten
- [ ] keine Mitgliedschafts-/Member-Rollen-Ereignisse im Audit
- [ ] Systemrolle `member` nicht manuell administrierbar

### Nur `board_member`

- [ ] `/vorstand` erreichbar
- [ ] Mitgliedschaften, Dokumente und Zustimmungen erreichbar
- [ ] `/verwaltung` nicht erreichbar
- [ ] `/koordination` nicht erreichbar

### `administration` + `board_member`

- [ ] Verwaltung und Vorstand erreichbar
- [ ] Mitgliedschaftsinformationen in zulässigen Kontexten sichtbar
- [ ] mitgliedschaftsbezogene Audit-Ereignisse sichtbar

### Koordination

- [ ] `/koordination` erreichbar
- [ ] `/verwaltung` und `/vorstand` nicht erreichbar

### Normales Mitglied

- [ ] kein interner Verwaltungs-/Vorstands-/Koordinationszugriff
- [ ] regulärer Konto-/Portalbereich weiterhin erreichbar

## 3. Fehler- und Leerzustände

Stichprobenartig prüfen:

- [ ] Personenliste ohne Suchtreffer
- [ ] Person ohne Portal-Konto
- [ ] kontoabhängige Schnellaktionen bei fehlendem/gesperrtem Konto
- [ ] abgelaufene oder bereits verwendete Einladung
- [ ] direkte URL auf nicht erlaubten internen Bereich ergibt 403 statt Datenleck
- [ ] fehlgeschlagene E-Mail-Zustellung wird verständlich dargestellt
- [ ] lange Namen, E-Mail-Adressen und Ortsangaben zerstören Tabellenlayout nicht

## 4. Design- und Accessibility-QA

Die verbindlichen Kriterien stehen in `docs/09_DESIGN_QA_CHECKLIST.md`; die reproduzierbare Ausführung steht in `docs/26_DESIGN_QA_EXECUTION.md`. Tracking: Issue #16.

Mindestens dokumentieren:

- [ ] Responsive: 320 px
- [ ] Responsive: 375 px
- [ ] Responsive: 768 px
- [ ] Responsive: 1024 px
- [ ] Responsive: 1280 px und größer
- [ ] Tastaturnavigation einschließlich sichtbarem Fokus
- [ ] Dropdowns/Mobile-Menü/Escape-Verhalten
- [ ] Screenreader-Stichprobe
- [ ] Reduced Motion / erhöhter Kontrast / Forced Colors
- [ ] Chrome
- [ ] Firefox
- [ ] Edge
- [ ] Safari
- [ ] Print-Stichprobe

Gefundene Fehler werden als gezielte QA-Patches behoben und anschließend erneut geprüft. Manuelle Punkte werden nur nach tatsächlicher Durchführung abgehakt.

## 5. Repository- und Security-Gate

Die genaue manuelle Ausführung für #13/#14/#15 steht in `docs/25_GITHUB_SETTINGS_RUNBOOK.md`.

Vor Beta-Freigabe:

- [ ] #13: `Protect main` verlangt aktuellen Branch vor Merge (`strict`)
- [ ] #13: Probe-PR bestätigt Update-Pflicht, Conversation Resolution, Squash-only und Branch-Löschung
- [ ] #14: Actions-Einstellungen in GitHub manuell gegen Zielwerte geprüft
- [ ] #15: Security-and-quality-Schalter in GitHub manuell gegen Zielwerte geprüft
- [ ] finaler `main` hat grünes `Quality`
- [ ] finaler `main` hat grüne `Static Analysis`
- [ ] finaler `main` hat grüne Security-Checks
- [ ] PHP 8.4.1 und PHP 8.5 sind grün

Nicht über den Connector auslesbare GitHub-Schalter werden nicht aufgrund von Annahmen als bestanden markiert.

## 6. Abschlussdokumentation

Nach erfolgreicher manueller Abnahme:

- [ ] `docs/09_DESIGN_QA_CHECKLIST.md` Browser-Matrix aktualisieren
- [ ] `docs/10_DESIGN_SYSTEM_STATUS.md` auf freigegeben/frozen aktualisieren
- [ ] `docs/22_DESIGN_SYSTEM_V1_FREEZE.md` als erfüllt dokumentieren
- [ ] `docs/99_CURRENT_HANDOFF.md` auf Hosting/Staging umstellen
- [ ] Issue #16 schließen
- [ ] Issue #22 schließen
- [ ] Issue #19 als nächste aktive Phase übernehmen

Die bereits vorbereitete Betriebs-Entscheidungsmatrix in `docs/27_OPERATIONS_DECISION_MATRIX.md` darf vor diesem Gate als Planungsgrundlage dienen, aktiviert aber Staging nicht vorzeitig.

## Abnahmeprotokoll

Erst nach tatsächlicher Durchführung ausfüllen:

- Datum:
- Testumgebung / Commit:
- Browser / Betriebssysteme:
- getestete Rollen:
- GitHub-Settings-Gate:
- offene Befunde:
- zugehörige Tickets:
- Ergebnis: `bestanden` / `nicht bestanden`
