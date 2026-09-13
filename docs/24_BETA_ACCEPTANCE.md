# Funktionale Beta-Abnahme

## Status

Die fachlichen Beta-Bausteine sind implementiert. Dieses Dokument ist das gemeinsame Abnahme-Gate vor Hosting und Staging.

Automatisierte Referenzbasis beim Erstellen dieser Checkliste:

- `main`: `959b7810a978078d0fbd9097919e0fc096a48cb5`
- CI: grün
- Security: grün
- Designsystem: technische Basis vollständig, manuelle QA aus #16 offen

Automatische Checks ersetzen die folgenden manuellen Produkt- und Browserprüfungen nicht.

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

Die verbindliche Detailcheckliste steht in `docs/09_DESIGN_QA_CHECKLIST.md` und Issue #16.

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

Gefundene Fehler werden als gezielte QA-Patches behoben und anschließend erneut geprüft.

## 5. Repository- und Security-Gate

Vor Beta-Freigabe:

- [ ] #13: `Protect main` verlangt aktuellen Branch vor Merge (`strict`)
- [ ] #14: Actions-Einstellungen in GitHub manuell gegen Zielwerte geprüft
- [ ] #15: Security-and-quality-Schalter in GitHub manuell gegen Zielwerte geprüft
- [ ] finaler `main` hat grünes `Quality`
- [ ] finaler `main` hat grüne `Static Analysis`
- [ ] finaler `main` hat grüne Security-Checks
- [ ] PHP 8.4.1 und PHP 8.5 sind grün

## 6. Abschlussdokumentation

Nach erfolgreicher manueller Abnahme:

- [ ] `docs/09_DESIGN_QA_CHECKLIST.md` Browser-Matrix aktualisieren
- [ ] `docs/10_DESIGN_SYSTEM_STATUS.md` auf freigegeben/frozen aktualisieren
- [ ] `docs/22_DESIGN_SYSTEM_V1_FREEZE.md` als erfüllt dokumentieren
- [ ] `docs/99_CURRENT_HANDOFF.md` auf Hosting/Staging umstellen
- [ ] Issue #16 schließen
- [ ] Issue #22 schließen
- [ ] Issue #19 als nächste aktive Phase übernehmen

## Abnahmeprotokoll

Erst nach tatsächlicher Durchführung ausfüllen:

- Datum:
- Testumgebung / Commit:
- Browser / Betriebssysteme:
- getestete Rollen:
- offene Befunde:
- Ergebnis: `bestanden` / `nicht bestanden`
