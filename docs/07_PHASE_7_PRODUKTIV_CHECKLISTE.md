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
- [ ] Branch muss vor Merge zwingend auf aktuellem `main` sein – derzeit bewusst nicht erzwungen

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

- [ ] Hosting- und Servermodell festgelegt
- [ ] Staging-Environment eingerichtet und getestet
- [ ] Deployment-Verfahren reproduzierbar getestet
- [ ] Rollback dokumentiert und praktisch getestet
- [ ] Backup-Konzept dokumentiert
- [ ] Restore aus Backup praktisch getestet
- [ ] Produktions-Secrets ausschließlich im Secret Store
- [ ] `.env`-Handhabung für Produktion geprüft; keine produktive `.env` versioniert
- [ ] `APP_DEBUG=0`
- [ ] HTTPS aktiv
- [ ] Mailversand getestet
- [ ] Queue/Scheduler für Produktion geprüft
- [ ] Admin-Zugänge geprüft
- [ ] Demo-/Entwicklungszugänge entfernt oder deaktiviert
- [ ] DSGVO-Prozesse getestet
- [ ] Audit-Log geprüft
- [ ] Health Check definiert und getestet
- [ ] Logging/Monitoring für Produktion festgelegt

## Release-Freigabe

Vor einem produktiven Release müssen sämtliche für den konkreten Betrieb relevanten offenen Punkte oben einzeln bewertet sein. Ein grünes `main` allein ist keine Produktionsfreigabe.
