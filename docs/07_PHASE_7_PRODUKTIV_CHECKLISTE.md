# Phase 7 – Produktiv-Checkliste

Diese Checkliste bildet ausschließlich den verifizierten Stand ab. Vorhandene Dateien oder Workflows werden nicht automatisch als betriebsbereit gewertet; produktionsbezogene Punkte bleiben bis zur echten Staging-/Produktionsabnahme offen.

## Repository

- [x] README vorhanden
- [x] SECURITY.md vorhanden
- [x] CONTRIBUTING.md vorhanden
- [x] CODE_OF_CONDUCT.md vorhanden
- [ ] CHANGELOG.md vor erstem Release auf Vollständigkeit prüfen
- [x] LICENSE geklärt (MIT)
- [x] Issue Templates vorhanden
- [x] PR Template vorhanden
- [x] CODEOWNERS gepflegt
- [x] `main` durch Ruleset geschützt
- [x] Force Push / Non-Fast-Forward auf `main` blockiert
- [x] Branch-Löschung auf `main` blockiert
- [x] nur gewünschte Merge-Methode aktiviert (Squash)
- [x] gemergte Head-Branches werden automatisch gelöscht
- [ ] Branch muss vor Merge zwingend auf aktuellem `main` sein – `strict_required_status_checks_policy` ist derzeit noch `false`

## Qualität und Security

- [x] CI auf Probe-PR grün
- [x] PHP 8.4.1 und 8.5 in CI geprüft
- [x] Static Analysis mit Larastan/PHPStan aktiv und grün
- [x] Composer Audit grün
- [x] npm Audit grün
- [x] Dependency Review auf Pull Requests aktiv und grün
- [x] High-Confidence Secret Scan aktiv und grün
- [x] Dependabot-Konfiguration für GitHub Actions, Composer und npm vorhanden
- [ ] Dependabot Alerts direkt in GitHub verifiziert
- [ ] Dependabot Security Updates direkt in GitHub verifiziert
- [ ] Secret Scanning direkt in GitHub verifiziert
- [ ] Push Protection direkt in GitHub verifiziert
- [ ] Private Vulnerability Reporting direkt in GitHub verifiziert

Hinweis: PHP-CodeQL ist nicht Teil der aktuellen Pipeline. Die statische PHP-Analyse erfolgt über den Required Check `Static Analysis` mit Larastan/PHPStan.

## Designsystem vor Produktivbetrieb

- [ ] manuelle Responsive-Abnahme abgeschlossen
- [ ] vollständige Tastaturprüfung abgeschlossen
- [ ] Screenreader-Stichprobe abgeschlossen
- [ ] Forced Colors / High Contrast / Reduced Motion geprüft
- [ ] Browser-Matrix Chrome, Firefox, Edge und Safari abgeschlossen
- [ ] Print-Stichprobe abgeschlossen
- [ ] lange Namen, E-Mail-Adressen, URLs und Dateinamen geprüft
- [ ] Designsystem v1 gemäß `docs/22_DESIGN_SYSTEM_V1_FREEZE.md` ohne offene Punkte freigegeben

Der manuelle Design-Abschluss wird in Issue #16 verfolgt.

## Betrieb

Bereits entschieden:

- [x] Hosting-Anbieter: STRATO
- [x] Servermodell: eigene VM/VPS

Vor Staging/Produktion noch festzulegen und praktisch zu prüfen:

- [ ] Betriebssystem und Webserver festgelegt
- [ ] produktive PHP-Version innerhalb der unterstützten 8.4.1+-Basis festgelegt
- [ ] Datenbanksystem und Version festgelegt; MySQL 8.4 ist die aktuelle CI-Referenz
- [ ] Staging-Environment eingerichtet und getestet
- [ ] Deployment-Verfahren reproduzierbar getestet
- [ ] Frontend-Build-Pfad festgelegt (CI-Artefakt oder Build auf Zielserver; Node 22 ist CI-Referenz)
- [ ] persistenter privater Storage für Mitgliedschaftsdokumente eingerichtet
- [ ] Deployment verliert/überschreibt bestehende Mitgliedschaftsdokumente nicht
- [ ] Rollback dokumentiert und praktisch getestet
- [ ] Backup-Konzept für Datenbank **und** private Mitgliedschaftsdokumente dokumentiert
- [ ] Backup-Aufbewahrung und Backup-Ziel festgelegt
- [ ] Restore von Datenbank **und** privaten Mitgliedschaftsdokumenten praktisch getestet
- [ ] Konsistenz zwischen Datenbankeinträgen und wiederhergestellten Fachdateien geprüft
- [ ] Produktions-Secrets ausschließlich im Secret Store
- [ ] `.env`-Handhabung für Produktion geprüft; keine produktive `.env` versioniert
- [ ] `APP_DEBUG=0`
- [ ] HTTPS aktiv
- [ ] Mailversand getestet
- [ ] Queue Worker dauerhaft betrieben und überwacht
- [ ] Scheduler dauerhaft betrieben und überwacht
- [ ] Worker-Restart beim Deployment getestet
- [ ] Admin-Zugänge geprüft
- [ ] Demo-/Entwicklungszugänge entfernt oder deaktiviert
- [ ] DSGVO-Prozesse getestet
- [ ] Audit-Log geprüft
- [ ] Health Check definiert und getestet
- [ ] Logging/Monitoring für Produktion festgelegt

## Release-Freigabe

Vor einem produktiven Release müssen sämtliche für den konkreten Betrieb relevanten offenen Punkte oben einzeln bewertet sein. Ein grünes `main` allein ist keine Produktionsfreigabe.
