# Entwicklungsroadmap

Diese Datei ist die zentrale Reihenfolge für die nächsten Entwicklungsblöcke. Detailanforderungen bleiben in den jeweiligen Phasen- und Fach-Dokumenten.

## Grundregel

Breite neue Feature-Arbeit beginnt erst auf einer stabilen Entwicklungsbasis. Repository-Schutz, CI/Security und der Designsystem-Vertrag werden nicht für neue Fachmodule umgangen.

## Milestone 0 – Repository Stabilization

**Status: abgeschlossen**

Ergebnis:

- CI-Workflow repariert und stabilisiert
- unterstützte PHP-Basis auf 8.4.1+ vereinheitlicht
- PHP 8.4.1 und 8.5 in CI geprüft
- nicht funktionierende PHP-CodeQL-Konfiguration entfernt
- Larastan/PHPStan als `Static Analysis` integriert
- stabile Check-Namen hergestellt

Verbindliche CI-Basis:

- `Quality`
- `Static Analysis`
- PHP compatibility 8.4.1
- PHP compatibility 8.5

Security-Basis:

- `Composer Audit`
- `NPM Audit`
- `Dependency Review`
- `Secret Scan`

## Milestone 1 – GitHub Settings und `main`-Schutz

**Status: weitgehend abgeschlossen; ein Ruleset-Punkt sowie UI-Prüfungen offen**

Tracking: #13, #14, #15

Verifiziert umgesetzt:

- Squash Merge an
- Merge Commits aus
- Rebase Merge aus
- automatische Head-Branch-Löschung an
- Update-Branch-Funktion an
- aktives Ruleset `Protect main`
- PR-Pflicht
- Conversation Resolution
- lineare Historie
- Schutz vor Löschung und Non-Fast-Forward
- kein Ruleset-Bypass
- Required Checks: `Quality`, `Static Analysis`, `Composer Audit`, `NPM Audit`, `Dependency Review`, `Secret Scan`

Noch offen:

- #13: Branch vor Merge zwingend auf neuesten `main`-Stand bringen (`strict_required_status_checks_policy` steht aktuell auf `false`)
- #14: nicht über den Connector auslesbare Actions-Einstellungen manuell prüfen
- #15: nicht über den Connector auslesbare Security-and-quality-Schalter manuell prüfen
- spätere Review-/Approval-Regeln bei mehreren verlässlichen Reviewern

## Milestone 2 – Designsystem v1 abschließen

**Status: technische Basis fertig, manuelle QA offen**

Tracking: Issue #16

Noch manuell abzunehmen:

- Responsive: 320, 375, 768, 1024, 1280+ px
- Tastaturnavigation und Fokusführung
- Screenreader-Stichprobe
- Forced Colors / High Contrast
- Reduced Motion und erhöhter Kontrast
- Chrome, Firefox, Edge und Safari
- Print-Stichprobe
- lange Namen, E-Mail-Adressen, URLs und Dateinamen

Abschluss ausschließlich nach `docs/22_DESIGN_SYSTEM_V1_FREEZE.md`. Es dürfen keine offenen QA-Punkte verbleiben.

## Milestone 3 – Dokumentation konsolidieren

**Status: abgeschlossen; laufende Statuspflege bleibt Teil jedes Abschlusses**

Ergebnis:

- Gesamtplan an Ist-Zustand angeglichen
- Ruleset und Merge-Konfiguration dokumentiert
- CI-/PHP-/Larastan-Stand synchronisiert
- obsolete PHP-CodeQL-Ziele entfernt
- Produktiv-Checkliste nur mit verifizierten Punkten abgehakt
- aktuelle und zukünftige Arbeit über diese Roadmap zusammengeführt

## Aktuelles Gate – Funktionale Beta-Abnahme

**Status: Feature-Implementierung abgeschlossen, manuelle Abnahme offen**

Tracking: #22 und `docs/24_BETA_ACCEPTANCE.md`

Die wesentlichen Beta-Fachblöcke sind umgesetzt:

- Personenverwaltung (#23)
- Mitgliedschafts-Lebenszyklus (#25)
- Audit-Ansichten (#27)
- Mitgliedschaftsdokumente und Zustimmungsnachweise (#29)
- Portal-Einladungen und Kommunikationsverwaltung (#30)
- capability-basierte fachliche Berechtigungen (#33)
- Trennung Verwaltung / Vorstand / Koordination (#37)
- Nacharbeit Personenliste und Schnellaktionen (#43)

Vor dem Wechsel zu Hosting/Staging bleiben:

- End-to-End-Test Person → Mitgliedschaft → Einladung → Konto → Login/2FA
- Rollen-/Berechtigungsgrenzen manuell prüfen
- Fehler-/Leerzustände stichprobenartig prüfen
- Design-/Accessibility-QA aus #16 abschließen
- Repository-/Security-Gate aus #13/#14/#15 abschließen
- Beta-Abnahme dokumentieren und #22 schließen

Die konkrete Abfolge steht in `docs/24_BETA_ACCEPTANCE.md`.

## Milestone 4 – Hosting- und Betriebsarchitektur

**Status: offen; bis zum erfolgreichen Beta-Gate zurückgestellt**

Tracking: Issue #19

Vor Deployment-Code verbindlich entscheiden:

- Hosting-/Servermodell
- Betriebssystem und Webserver
- produktive PHP-Version
- Datenbank
- Queue und Scheduler
- Mailversand
- Storage und persistente Dateien
- Build auf Server vs. vorgebautes Artifact
- SSH-/Key-Management bzw. alternatives Deployment-Verfahren
- Secret-Verwaltung
- Migrationen und Maintenance Mode
- Health Check
- Logging und Monitoring
- Backup, Restore und Rollback
- Verantwortlichkeiten im Betrieb

Bereits entschieden: STRATO, eigene VM/VPS.

Definition of Done: Die offenen Voraussetzungen aus `docs/05_PHASE_5_DEPLOYMENT_PLAN.md` und Issue #19 sind konkret beantwortet.

## Milestone 5 – Staging

**Status: offen**

Zielbild:

- geschütztes GitHub Environment `staging`
- eigene Staging-Secrets, URL und Datenbank
- zunächst manuell gestarteter Deployment-Workflow
- Deployment aus bekanntem grünen `main`-Commit
- Build/Artifact, Upload, Dependencies, Migrationen, Caches, Worker/Scheduler, Health Check und Smoke Test nachvollziehbar
- Login, Registrierung, 2FA, Konto, Verwaltung, Vorstand, Kommunikation, Mail, Sessions, Queue, Scheduler, Dateien und Fehlerseiten prüfen
- Rollback praktisch durchführen
- Restore aus Backup praktisch durchführen

## Milestone 6 – Release-Prozess

**Status: offen**

Ablauf:

`Pull Request` → grünes `main` → Changelog prüfen → SemVer-Tag `vX.Y.Z` → GitHub Release → Deployment

Vor Produktion mindestens einen 0.x-Probelauf durchführen und die Rückverfolgbarkeit von Commit, Tag, Release und Deployment prüfen.

## Milestone 7 – Produktion

**Status: offen**

Erst nach erfolgreichem Staging-/Release-Probelauf:

- geschütztes GitHub Environment `production`
- Production-Secrets ausschließlich im Secret Store
- geeignete Reviewer-/Environment-Regeln
- Release-basierter Production-Deployment-Workflow
- Backup vor risikobehafteten Änderungen
- Migrationen, Cache, Worker, Health Check und Smoke Test
- definierter Fehler- und Rollback-Pfad
- vollständige Abnahme von `docs/07_PHASE_7_PRODUKTIV_CHECKLISTE.md`

## Milestone 8 – Fachliche Weiterentwicklung nach Beta

**Status: spätere Priorisierung nach stabiler Betriebsbasis**

Der ursprünglich hier vorgesehene Beta-Kern wurde vorgezogen und ist bereits umgesetzt. Neue Fachmodule werden nach dem Betriebs-/Release-Gate anhand realer Anforderungen priorisiert.

Mögliche spätere Themen sind unter anderem:

- Finanzen und Fundraising
- vereinsrechtliche Fachvorgänge
- Koordination von Mitarbeitenden/Freiwilligen
- Seminar- und Schulkoordination
- weitergehende Mitglieder- und Teamfunktionen
- konfigurierbare Benachrichtigungen
- Tickets/Serviceprozesse
- Newsletter-/Massenmailing, falls fachlich beschlossen

Keine abstrakte komplexe RBAC- oder Komponentenstruktur auf Vorrat bauen.

## Empfohlene Reihenfolge

Aktuell parallel:

`Beta-End-to-End-Abnahme (#22)` + `Design-QA (#16)` + `Repository/Security-Gate (#13/#14/#15)`

Danach:

`M4 Hosting/Betriebsarchitektur (#19)` → `M5 Staging` → `M6 Release-Probelauf` → `M7 Produktion` → `M8 neue Fachmodule`

Hosting/Staging beginnt erst, wenn die funktionale Beta als abgenommen dokumentiert ist. Manuelle QA wird nicht durch grüne CI ersetzt.
