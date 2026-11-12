# Protokoll (Modul Audit)

Unter `/verwaltung/protokoll`: ein **unveränderliches** Protokoll sicherheits- und verwaltungsrelevanter Ereignisse
(wer hat wann was getan). Tabelle `audit_entries`, Modell `AuditEntry` (nur anlegen; Ändern und Einzellöschen werfen eine
Ausnahme).

## Berechtigung

`audit.view` (ansehen, filtern, exportieren): **Administration und Vorstand**. Die Geschäftsstelle sieht das Protokoll nicht;
das ist eine Zeile in `AccessGovernance/Support/Capabilities.php`.

## Was protokolliert wird

| Gruppe | Ereignisse |
|---|---|
| Anmeldung | angemeldet (mit/ohne Zwei-Faktor), Anmeldung fehlgeschlagen (nur bestehende Konten), Zwei-Faktor-Code falsch |
| Konto | Passwort geändert, Passwort festgelegt/zurückgesetzt (auch Einladung), E-Mail geändert (alt/neu), andere Sitzungen beendet, Profil geändert (Feldnamen) |
| Zwei-Faktor | eingerichtet, entfernt, Wiederherstellungscodes erneuert |
| Nutzerverwaltung | Konto angelegt, Einladung gesendet, gesperrt, entsperrt, Zwei-Faktor zurückgesetzt, Passwort-Link gesendet |
| Rollen | vergeben (Rolle, bis, Kommentar), beendet |
| Personen | angelegt, geändert (nur **Feldnamen**, nie die Werte), gelöscht |
| Protokoll | Export |

Jeder Eintrag hat Zeitpunkt, Ereignis, Akteur, Betroffene, Angaben und IP-Adresse. Akteur und Betroffene stehen zusätzlich als Text
im Eintrag, damit er auch nach Änderungen oder Löschungen lesbar bleibt.

## Datenschutz und Sicherheit

- **Nie** im Protokoll: Passwörter, Codes, Tokens, Geheimnisse. Der Kontext enthält nur unkritische Angaben (`AuditLog` kürzt Texte
  auf 500 Zeichen und verwirft Objekte).
- Fehlgeschlagene Anmeldungen an **unbekannten** Adressen werden nicht gespeichert (kein Müll und keine fremden Adressen im Protokoll).
  Fehlversuche sind ohnehin durch die Sperre nach fünf Versuchen begrenzt.
- Ein Fehler beim Protokollieren bricht die eigentliche Aktion nicht ab, er wird gemeldet (`report()`).
- Aufbewahrung: `config/audit.php` (`AUDIT_RETENTION_DAYS`, Standard 730 Tage). `php artisan audit:prune [--days=N]` löscht
  ältere Einträge (mindestens 30 Tage). Die Frist gehört in die Datenschutzdokumentation des Vereins; ohne den Befehl bleibt alles erhalten.
- CSV-Export: UTF-8 mit Kennzeichen (Excel), Semikolon, höchstens 10 000 Zeilen, Zellen mit `= + - @` bekommen ein Hochkomma
  (Schutz vor Formeln). Der Export selbst wird protokolliert.

## Seiten

- **Liste:** neueste zuerst, 25 pro Seite, Suche (Akteur/Betroffene), Filter Gruppe, Ereignis, Zeitraum, aktive Filter als Tags, ganze Zeile klickbar.
- **Eintrag:** Details mit Angaben; Akteur und Betroffene verlinken, wenn der Betrachter die Nutzer- bzw. Personenverwaltung sehen darf.
- **Verlauf** auf den Detailseiten von Nutzern und Personen (letzte 8 Einträge, Link „Im Protokoll öffnen“ mit Filter `subject=user:ID|person:ID`).

## Neues Ereignis hinzufügen

1. Fall in `Audit/Enums/AuditEvent.php` ergänzen (Wert, `label()`, `category()`, ggf. `tone()`).
2. An der Stelle der Aktion `AuditLog::record(AuditEvent::…, $akteur, $betroffener, ['schluessel' => 'wert'])` aufrufen.

Tests: `tests/Feature/Audit/AuditTest.php`. Migration: `php artisan migrate`.
