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

**Status: Basis abgeschlossen; einzelne Entscheidungen/Settings bleiben offen**

Umgesetzt:

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

Bewusst offen:

- Branch vor Merge zwingend auf neuesten `main`-Stand bringen (`strict`)
- spätere Review-/Approval-Regeln bei mehreren verlässlichen Reviewern
- manuelle Verifikation der nicht vollständig auslesbaren GitHub-Security-Settings

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

**Status: abgeschlossen**

Ergebnis:

- Gesamtplan an Ist-Zustand angeglichen
- Ruleset und Merge-Konfiguration dokumentiert
- CI-/PHP-/Larastan-Stand synchronisiert
- obsolete PHP-CodeQL-Ziele entfernt
- Produktiv-Checkliste nur mit verifizierten Punkten abgehakt
- aktuelle und zukünftige Arbeit über diese Roadmap zusammengeführt

Abgeschlossen mit PR #18.

## Milestone 4 – Hosting- und Betriebsarchitektur

**Status: offen – Entscheidungstask angelegt**

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

Definition of Done: Die offenen Voraussetzungen aus `docs/05_PHASE_5_DEPLOYMENT_PLAN.md` und Issue #19 sind konkret beantwortet.

## Milestone 5 – Staging

**Status: offen**

Zielbild:

- geschütztes GitHub Environment `staging`
- eigene Staging-Secrets, URL und Datenbank
- zunächst manuell gestarteter Deployment-Workflow
- Deployment aus bekanntem grünen `main`-Commit
- Build/Artifact, Upload, Dependencies, Migrationen, Caches, Worker/Scheduler, Health Check und Smoke Test nachvollziehbar
- Login, Registrierung, 2FA, Konto, Verwaltung, Mail, Sessions, Queue, Scheduler, Dateien und Fehlerseiten prüfen
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

## Milestone 8 – Fachliche Weiterentwicklung

**Status: offen; nach stabiler Betriebsbasis priorisieren**

### 8.1 Personen und Mitgliedschaften

- Personendaten
- Mitgliedschaft und Statusverlauf
- Verwaltungsliste/-detail
- Suche und Filter
- Berechtigungen
- Audit für Schreibvorgänge

### 8.2 Audit-Ansichten

- Zeit, Akteur, Objekt, Aktion, Grund und zulässige Metadaten
- Zugriffsschutz für sensible Informationen
- früh umsetzen, bevor viele weitere Schreibworkflows entstehen

### 8.3 Kommunikation

- Vorlagen und veröffentlichte Versionen
- Zustellstatus
- Fehler/Retry
- Vorschau
- Audit

### 8.4 Einladungen und Freigaben

- Person → Einladung → sicherer Token → Annahme → Konto → Freigabe
- Ablauf, Widerruf und erneuter Versand
- klare Statusmodelle und Audit

### 8.5 Feinere Berechtigungen

Berechtigungen aus echten Workflows ableiten, zum Beispiel:

- Personen lesen/bearbeiten
- Mitgliedschaften bearbeiten
- Rollen verwalten
- Kommunikation verwalten
- Audit lesen

Keine abstrakte komplexe RBAC-Struktur auf Vorrat bauen.

## Empfohlene Reihenfolge

Aktuell parallel:

`M2 manuelle Design-QA (#16)` und `M4 Betriebsentscheidungen (#19)`

Danach:

`M5 Staging` → `M6 Release-Probelauf` → `M7 Produktion` → `M8 Fachmodule`

M2 kann parallel zu reinen Dokumentations- und Planungsarbeiten laufen, darf aber nicht ohne die manuelle QA als abgeschlossen markiert werden. M5 beginnt erst, wenn M4 ausreichend entschieden ist.
