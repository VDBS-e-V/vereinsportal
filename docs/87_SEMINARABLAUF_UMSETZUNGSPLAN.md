# Seminarablauf: Umsetzungsplan

Grundlage: `docs/86_SEMINARABLAUF_PLANUNG.md` (Ablauf, Regeln, Rollen, Ordnerstruktur, Abgleich). Dieser Plan setzt genau diesen Ablauf um und **ersetzt den bisherigen Seminarablauf vollständig**. Stand: Entwurf, nichts davon ist umgesetzt.

## 1. Grundsätze

1. **Ersetzen, nicht ergänzen.** Der alte Prozess (Status, Übergänge, Sofort-Zusagen, Mappe als ZIP, Abschluss-Checkliste als eigener Weg) wird entfernt und durch den neuen ersetzt. Es bleiben nur Bausteine, die der neue Ablauf aufruft: Kostenvoranschlag-Berechnung (`QuoteCalculator`, Preislisten, Texte), Vertragserzeugung der Teamenden (`Contracts`), Dokumentenablage (`SeminarDocuments`), Mailversand, Protokoll (`AuditLog`), Benachrichtigungseinstellungen.
2. **Kostenstelle bleibt außen vor.** Das Seminar liefert nur das Ereignis „abgeschlossen“. Das Feld `cost_center` bleibt unverändert.
3. **Eine einzige Quelle für den Ablauf:** ein Dienst `SeminarWorkflow` mit allen erlaubten Übergängen, Bedingungen und Rechten. Kein Controller und keine Ansicht ändert einen Status selbst.
4. **Jeder Statuswechsel** steht im Verlauf des Seminars und im Protokoll, mit Person und Begründung, wo vorgesehen.
5. **Mails:** immer per E-Mail, außer die Person schaltet sie im Konto ab. KVA, Vertrag sowie Zu- und Absage sind Pflicht-Mails.

## 2. Neues Statusmodell (ersetzt `SeminarStatus`)

| Status | Bedeutung | Wer setzt ihn | Bedingung |
|---|---|---|---|
| `request` Anfrage | Schule hat angefragt | Schule | – |
| `in_review` In Prüfung | Geschäftsstelle prüft, ggf. Rückfrage | Geschäftsstelle | – |
| `quote_sent` KVA gesendet | KVA liegt bei der Schule | Geschäftsstelle | KVA freigegeben (Vorstand oder Koordination) |
| `released` Freigegeben | Seminar darf geplant werden | Geschäftsstelle | **Basis `contract`:** Vertrag der Schule unterschrieben (Datei und Datum). **Basis `commitment`:** verbindliche Zusage der Schule, Begründung Pflicht, Vertrag wird nachgereicht |
| `planned` Geplant | Konkretes Seminar mit Terminen und Sollzahl | Koordination | mindestens ein Termin, Sollzahl gesetzt |
| `tendered` Ausgeschrieben | Team wird gesucht | Koordination | Bewerbungsfrist gesetzt |
| `team_selected` Team gewählt | Entwurf der Auswahl liegt vor | Koordination | mindestens eine Zusage im Entwurf; Warnung, wenn Sollzahl nicht erreicht |
| `team_confirmed` Team bestätigt | Zu- und Absagen sind versendet | Koordination | Seminarleitung bestimmt |
| `prepared` Vorbereitet | Unterlagen, Ordner und Onboarding stehen | Koordination | Nextcloud-Ordner angelegt (oder bewusst manuell), Onboarding-Termin gesetzt |
| `held` Durchgeführt | Seminar fand statt | Seminarleitung | Termine liegen in der Vergangenheit |
| `reported` Berichtet | Verträge und Ergebnisbericht hochgeladen und geprüft | Seminarleitung lädt hoch, Koordination prüft | Pflichtdokumente vorhanden |
| `completed` Abgeschlossen | Bericht an die Schule gesendet | Koordination | Ergebnislink gesendet; löst das Ereignis `SeminarCompleted` aus |
| `rejected` Abgelehnt | Anfrage abgelehnt | Geschäftsstelle | Begründung Pflicht |
| `cancelled` Abgesagt | Seminar abgesagt | Koordination, Geschäftsstelle | Begründung Pflicht |

Weitere Regeln:
- **Neuer Termin nach Absage:** Die Koordination setzt neue Termine, wenn Vertrag und Preis gleich bleiben (zurück nach `planned`, Team wird neu angefragt). Ändert sich Leistung oder Preis, setzt die Geschäftsstelle auf `in_review` zurück.
- **Rückwärts:** Nur ausdrücklich definierte Rücksprünge, jeweils mit Begründung. Abgeschlossene Seminare öffnet nur der Vorstand, wie bisher.

## 3. Datenmodell

Neue Migrationen (Datum nach dem letzten Stand):

1. **`partners`:** `coordination_area` (string, nullable) in den Stammdaten der Schule. Pflege im Formular der Schule.
2. **`seminars`:**
   - `release_basis` (`contract`|`commitment`), `release_reason`, `released_by_user_id`, `released_at`
   - `quote_approved_by_user_id`, `quote_approved_at`
   - `school_contract_signed_at` (Datum der Unterschrift; die Datei liegt als `SeminarDocument` der Art „Vertrag Schule“)
   - `team_target` (umbenannt aus `team_needed`)
   - `team_confirmed_at`, `team_confirmed_by_user_id`
   - `onboarding_at`, `onboarding_link`
   - `report_due_at`
   - `held_at`, `held_confirmed_by_user_id`
   - `reported_at`, `completed_at`
   - `nextcloud_folder_path`, `results_share_id`, `results_share_url`, `results_expires_at`
   - `retention_until` (Ergebnisordner)
   - `series_note` entfällt nicht, Einzeltage bleiben als `SeminarSession`.
3. **`seminar_applications`:** `decision_draft` (`confirm`|`decline`|null), `waitlist_position` (Zahl, null = nicht auf der Warteliste), `confirmation_sent_at`. Status bleibt `applied|confirmed|declined|withdrawn`, die Entwurfsentscheidung steht in `decision_draft` und wirkt erst nach „Team bestätigen“.
4. **`seminar_events`:** neue Ereignistypen (Freigabe, KVA-Freigabe, Teambestätigung, Ordner angelegt, Ergebnislink, Mail gesendet).
5. **Datenübernahme:** Siehe Abschnitt 8.

## 4. Seiten und Zuständigkeiten (ersetzt die bisherigen Seminarseiten)

| Bereich | Seite | Inhalt |
|---|---|---|
| Geschäftsstelle | **Seminare** (`/geschaeftsstelle/seminare`) | Liste aller Seminare mit Filter Status, Koordinationsgebiet, Schule, Schuljahr. Aktionen: prüfen, KVA erstellen und freigeben lassen, senden, Vertrag der Schule erfassen, freigeben (Vertrag oder Zusage), Teamverträge erzeugen und drucken, ablehnen |
| Geschäftsstelle | Seminar (Detail) | Verlauf, KVA, Dokumente, Verträge, Nachrichten an die Schule |
| Koordination | **Meine Seminare** (`/koordination/seminare`) | Seminare in `released` bis `completed`, Aktionen je Status: planen, ausschreiben, Auswahl, bestätigen, vorbereiten, prüfen, abschließen |
| Koordination | Auswahl | Interessenten mit Verfügbarkeit, Zusage und Absage per Klick (Entwurf), Sollzahl-Warnung, Wartelistenreihenfolge, Seminarleitung wählen, „Team bestätigen“ |
| Teamende | Seminare (bestehend, angepasst) | Ausschreibungen, Interesse mit Verfügbarkeit, eigene Zusagen, Ordner-Link |
| Seminarleitung | eigene Seminarseite | Durchführung bestätigen, Verträge und Bericht hochladen, Stichtag und Erinnerung |
| Schulen | Bereich Schulen (angepasst) | Anfrage, KVA ansehen und annehmen, Bericht und Ergebnislink, Feedbackformular |

Berechtigungen (neu oder angepasst):
- `seminars.review` (Geschäftsstelle): Prüfung, KVA, Vertrag der Schule, Freigabe, Ablehnung
- `seminars.quote_approve` (Vorstand, Koordination): KVA freigeben
- `seminars.plan` (Koordination): planen, ausschreiben, Auswahl, vorbereiten, abschließen
- `seminars.lead` (Seminarleitung, nur das eigene Seminar, über die Leitungskennzeichnung): Durchführung und Berichte
- `seminars.apply` (Teamende) bleibt

## 5. Mitteilungen

Alle Mails laufen über einen Dienst `SeminarNotifications`, der die Einstellung im Konto prüft. **Pflicht-Mails** (nicht abschaltbar): KVA, Vertrag, Zusage, Absage. Jede Mail wird im Verlauf des Seminars vermerkt (ohne Inhalt).

| Auslöser | Empfänger |
|---|---|
| Anfrage eingegangen | Schule (Bestätigung), Geschäftsstelle |
| Rückfrage, Ablehnung | Schule |
| KVA gesendet (Pflicht) | Schule |
| Freigabe | Koordination |
| Ausschreibung | Teamende (nach Eignung) |
| Fristende nah | Teamende ohne Rückmeldung |
| Team bestätigt: Zusage (Pflicht) | Team |
| Team bestätigt: Absage (Pflicht, mit Hinweis auf Warteliste) | übrige Interessenten |
| Nachrücken | nachrückende Person (Zusage, Pflicht) |
| Seminarleitung bestimmt | die Person |
| Onboarding-Einladung | Team |
| Briefing | Schule |
| Absage, Verlegung | Team, Schule |
| Stichtag Berichte, Erinnerung | Seminarleitung, Koordination |
| Bericht und Ergebnislink | Schule |
| Rückmeldung angefragt | Schule |

Personen ohne Konto (z. B. Schule vor der Anmeldung, Teamende über den Bewerbungslink) bekommen immer die E-Mail.

## 6. Nextcloud-Anbindung (neu)

Ziel: Ordnerstruktur und Rechte automatisch, Ergebnislink mit Ablaufdatum.

- **Struktur:** Team folder „Koordination“ → Koordinationsgebiet → Schule → Schuljahr → Seminar oder Reihe → ggf. Tag, darin „Unterlagen (Team)“ und „Ergebnisse (Schule)“. Namen werden bereinigt (keine Sonderzeichen), das Seminar trägt seine Nummer.
- **Technik:** Dienst `NextcloudClient` (Schnittstelle plus echte und Test-Implementierung) mit WebDAV (Ordner anlegen), OCS Share API (Link mit Ablaufdatum und Upload-Recht auf „Ergebnisse“) und der ACL-Schnittstelle der Team folders (Teamende sehen nur ihr Seminar). Anmeldung über ein **Dienstkonto mit App-Passwort**, das nur in der Umgebung (`.env`) steht, nie im Code.
- **Ablauf:** Beim Wechsel nach `team_confirmed` wird der Ordner per Auftrag in der Warteschlange angelegt (wiederholbar, idempotent). Beim Nachrücken oder Rücktritt werden die Rechte angepasst. Der Link entsteht erst bei `reported` und läuft frühestens ein Jahr nach Seminarende ab (Standard 2 Jahre, verlängerbar).
- **Ausfall:** Ist Nextcloud nicht erreichbar oder nicht konfiguriert, zeigt das Seminar „Ordner manuell anlegen“ und blockiert nichts. Fehler stehen im Verlauf und im Fehlerprotokoll.
- **Voraussetzungen auf Nextcloud-Seite (durch Sie):** Team folder „Koordination“ mit aktivierten erweiterten Rechten (ACL), ein Dienstkonto mit Verwaltungsrecht für diesen Ordner, App-Passwort.

## 7. Fristen und Löschen

- Ergebnisordner und Link: 2 Jahre ab Seminarende, verlängerbar. Vorher löscht nichts.
- Anfragen ohne Vertrag (abgelehnt, abgesagt): 1 Jahr nach Abschluss.
- Verträge und Belege mit Geldbezug: gesetzliche Fristen (mit Steuerberatung klären, bis dahin keine automatische Löschung).
- Die vorhandenen Läufe `PruneApplicants` und `PruneDocuments` werden auf diese Werte angepasst. Neuer Lauf für die Ergebnisordner.

## 8. Ersetzen und Datenübernahme

**Wird entfernt oder ersetzt**
- `SeminarStatus` (alte sieben Werte) und `canChangeTo` durch `SeminarWorkflow`
- `SeminarAdministration::changeStatus` und die Status-Route
- Sofort-Entscheidung in `SeminarTeamController::decide` und `TeamApplications::decide` (Zusage und Absage einzeln) durch Entwurf plus „Team bestätigen“
- `TeamInfo` („Team informieren“) wird Teil der Teambestätigung und des Onboarding-Versands
- Seminarmappe als ZIP (`SeminarFolder`, Route `seminars.folder`): **bleibt** (Entscheidung), zusätzlich kommt der Online-Druckplaner (Abschnitt 12)
- Abschluss-Checkliste als eigener Weg (`SeminarClosures`): Inhalt geht in die Übergänge `held`, `reported`, `completed` ein. Der Kennzahlen-Schnappschuss bleibt Teil von `completed`
- Seminarseiten und -ansichten: `seminars/index`, `show`, `_team`, `form`, `closure` werden neu gebaut
- Alte Verlaufstexte bleiben lesbar (Ereignistypen werden nur ergänzt)

**Bleibt als Baustein:** Quotes, Preislisten, Texte, Statistik, Verträge der Teamenden, Dokumente, Benachrichtigungseinstellungen, Bewerbungslink.

**Übernahme bestehender Seminare (Migration, mit Sicherung vorher)**

| Alt | Neu |
|---|---|
| `request` | `request` |
| `planning` | `in_review` |
| `scheduled` | `planned` |
| `confirmed` | `released` (Basis `contract`, wenn Vertrag erfasst, sonst `commitment` mit Hinweis „aus Altbestand“) |
| `done` | `held` |
| `completed` | `completed` |
| `cancelled` | `cancelled` |

Bewerbungen: `confirmed` und `declined` bleiben, `confirmation_sent_at` aus dem alten Versandzeitpunkt. Die Migration ist umkehrbar nur über die Sicherung. **Vor dem Ausführen auf Produktivdaten: Datenbanksicherung, Test mit einer Kopie.**

## 9. Umsetzung in Phasen

Jede Phase ist für sich lauffähig, hat Tests (Pest) und wird gepusht. Die Reihenfolge weicht von der Liste in Doc 86 ab, weil „vollständig ersetzen“ zuerst das neue Statusmodell verlangt.

| Phase | Inhalt | Ergebnis zum Prüfen |
|---|---|---|
| 1 | Neues Statusmodell, `SeminarWorkflow`, Migrationen und Datenübernahme, Berechtigungen, Feld Koordinationsgebiet an der Schule, alte Statuslogik entfernt | Seminare haben die neuen Status. Übergänge nur nach Regeln. Alle alten Seiten laufen mit den neuen Status |
| 2 | Geschäftsstelle: Seminarliste, Prüfung, KVA-Freigabe, Freigabe mit Vertrag oder Zusage, Ablehnung, Teamverträge von dort, Schulseite angepasst | Ablauf Schritte 1 bis 2c durchspielbar |
| 3 | Koordination: Meine Seminare, Planen, Ausschreiben, Auswahl als Entwurf, „Team bestätigen“, Warteliste und Nachrücken, Seminarleitung | Schritte 3 bis 6c durchspielbar |
| 4 | Mitteilungen: Dienst, Pflicht-Mails, Vorlagen, Einstellung im Konto, Verlaufsvermerke | alle Mails der Tabelle in Abschnitt 5 |
| 5 | Seminarleitung und Abschluss (danach Phase 5a: Online-Druckplaner): Durchführung, Uploads, Stichtag, Erinnerung, Prüfung, Bericht an die Schule, Feedback, Ereignis `SeminarCompleted` | Schritte 8c bis 12 durchspielbar |
| 6 | Nextcloud: Client, Ordner, Rechte, Ergebnislink, Aufträge, Ausfallverhalten | Ordner entstehen automatisch, Link läuft mit Ablaufdatum |
| 7 | Fristen: Löschläufe, Ergebnisordner, Verlängerung | Läufe nach den beschlossenen Werten |
| 8 | Aufräumen: alte Klassen, Routen und Ansichten löschen, Dokumentation und Changelog, Handbuch-Texte | nichts Altes bleibt übrig |

Tests je Phase: jeder erlaubte und jeder verbotene Übergang, Rechte je Rolle (auch „nur das eigene Seminar“), Mails inklusive Abschaltung und Ausnahme für Pflicht-Mails, Datenübernahme (Altstatus nach Neustatus), Nextcloud mit Test-Client.

## 10. Risiken und Voraussetzungen

- **Migration bestehender Daten:** Sicherung und Probelauf zwingend. Gibt es nur Testdaten, kann man sie verwerfen, dann entfällt die Übernahme.
- **Nextcloud:** Ohne Dienstkonto und aktivierte ACL gibt es Phase 6 nicht, die Phasen 1 bis 5 laufen unabhängig davon.
- **Umfang:** Rund 3.400 Zeilen bestehender Seminarcode werden berührt. Darum Phasen, jeweils mit eigener Prüfung.
- **Recht:** Löschfristen für Verträge und Belege vor Phase 7 mit der Steuerberatung klären. Texte der Schul- und Teammails vor dem Produktivbetrieb prüfen lassen.
- **Nicht umsetzbar von hier aus:** Alles ist nur syntaxgeprüft. Tests laufen in der CI, Nextcloud erst gegen Ihre Instanz.

## 11. Entscheidungen zu den offenen Fragen (bestätigt)

1. **Testbetrieb:** Alle Systeme laufen nur im Testbetrieb. Es gibt keine Daten, die erhalten bleiben müssen. Die Datenübernahme im Plan ist trotzdem als saubere Migration umgesetzt (Abschnitt 8), eine Sicherung ist erst bei echten Daten nötig.
2. **Seminarmappe bleibt**, als ZIP **und** als **Online-Druckplaner**. Das ersetzt den Eintrag „Seminarmappe entfernen“ in Abschnitt 8 (siehe Abschnitt 12).
3. **Der Vertrag mit der Schule wird im Portal erstellt.** Das ersetzt die Annahme „Datei hochladen“ (siehe Abschnitt 12).

## 12. Ergänzungen aus den Entscheidungen

### Seminarmappe als ZIP und Druckplaner

- **ZIP bleibt** (`SeminarFolder`): alle Dokumente eines Seminars nach Art in Unterordnern, mit Inhaltsverzeichnis und Prüfsummen.
- **Neu: Online-Druckplaner** am Seminar. Die Person (Koordination, Seminarleitung, Team) wählt dort **welche Seite wie oft gedruckt wird**:
  - je Dokument der Mappe: Seiten (alle oder Bereich wie „1-3, 5“), Anzahl der Exemplare
  - je Dokument: **Schwarzweiß oder Farbe**
  - je Dokument: **Elemente pro Blatt** (1, 2, 4 Seiten pro Blatt), einseitig oder doppelseitig
  - Ergebnis: eine Zusammenfassung (Blätter, Seiten Schwarzweiß und Farbe, Gesamtzahl) und eine **Druckliste als PDF** für Druckerei oder Büro. Der Druckplan wird am Seminar gespeichert und lässt sich ändern.
  - Je Dokument braucht der Planer die Seitenzahl. Das Portal liest sie beim Hochladen aus den PDF-Dateien (Word-Dateien werden beim Erzeugen als PDF abgelegt oder die Seitenzahl wird von Hand eingetragen).
- Phase: Der Druckplaner gehört in eine eigene **Phase 5a** nach der Seminarleitung (er braucht die Dokumente der Seminarleitung).

### Vertrag mit der Schule im Portal

- Das Portal erzeugt den **Schulvertrag** aus einer Vorlage mit den Daten des Seminars (Schule, Leistung, Termine, Preis aus dem freigegebenen KVA). Die Vorlage pflegt die Geschäftsstelle wie die Briefvorlagen und Vertragstexte. Das ist ein eigener Bereich neben dem Vertrag der Teamenden.
- Ablauf: Geschäftsstelle erzeugt den Vertrag aus der Seminarliste, druckt oder sendet ihn, die Schule unterschreibt, die Geschäftsstelle erfasst die Unterschrift (Datum, Scan als Dokument). Erst dann ist die Freigabe mit Basis „Vertrag“ möglich. Die Freigabe auf Zusage bleibt als Ausnahme.
- Phase: **Phase 2** (Geschäftsstelle) um die Vertragsvorlage und -erzeugung erweitert.

## 13. Stand der Umsetzung

- **Phase 1 umgesetzt** (nicht ausgeführt, nur Syntax geprüft, Tests laufen in der CI): neues Statusmodell, `SeminarWorkflow`, Migration `2027_01_10`, Koordinationsgebiet an der Schule, alle Stellen und Tests auf die neuen Status umgestellt, alte Statuslogik entfernt.
- **Phase 2 umgesetzt** (ebenfalls nur syntaxgeprüft, Tests in der CI):
  - Seite **Geschäftsstelle › Seminare** (`/geschaeftsstelle/seminare`) mit allen Seminaren, Filter nach Status und Koordinationsgebiet, Stand von KVA, Freigabe und Vertrag
  - neue Rechte `seminars.review` (Geschäftsstelle, Vorstand) und `seminars.quote_approve` (Vorstand, Koordination); Prüfung, KVA-Versand, Freigabe und Ablehnung nur noch mit `seminars.review`
  - **interne KVA-Freigabe** am Seminar; „KVA gesendet“ setzt sie voraus; eine Änderung des Kostenvoranschlags danach macht sie ungültig
  - Kasten „Prüfung und Freigabe“ am Seminar (Freigabegrund, Datum, Vertrag)
  - **Vertrag mit der Schule**: Vorlage mit Platzhaltern (versioniert, Seite „Vorlage Schulvertrag“), Erzeugen aus dem Seminar, Druckansicht, Unterschrift mit Datum erfassen, Stornieren; die Unterschrift füllt das Datum für die Freigabe mit Basis „Vertrag“
- **Phase 3 umgesetzt** (nur syntaxgeprüft, Tests in der CI):
  - Teamauswahl als **Entwurf**: je Interessent „Zusage“, „Absage“ oder „Zurücksetzen“ (nichts wird gesendet). Mit der ersten Zusage wechselt das Seminar auf „Team gewählt“.
  - **„Team bestätigen“**: Seminarleitung Pflicht, Warnung und ausdrückliche Bestätigung, wenn weniger Zusagen als die Sollzahl; setzt alle Entscheidungen um und sendet alle Mails gleichzeitig (Zusage, Absage, Warteliste); Seminar wechselt auf „Team bestätigt“.
  - **Warteliste und Nachrücken**: Wer ohne Markierung bleibt, kommt mit Platznummer auf die Warteliste; „Nachrücken“ bei freiem Platz sendet die Zusage.
  - Zusage, Absage und Wartelisten-Mail sind **Pflicht-Mails** und ignorieren die Einstellung im Konto.
  - Seite **Koordination › Meine Seminare** mit dem nächsten Schritt je Status.
  - Der alte Weg mit sofortiger Zu- oder Absage (`decide`, Route `team.decide`) ist entfernt; der Teamvorschlag legt jetzt Entwürfe an.
- **Phase 4 umgesetzt** (nur syntaxgeprüft, Tests in der CI):
  - Dienst `SeminarNotifications` mit einer Mail-Art `SeminarNoticeMail`: Eingangsbestätigung, Rückfrage, Ablehnung mit Begründung, KVA (Pflicht), Briefing, Absage, neue Termine an die Schule; Freigabe an die Koordination; Ausschreibung an Teamende; Hinweis an die Seminarleitung; Onboarding-Einladung und neue Termine an das Team.
  - Pflicht-Mails: KVA an die Schule, Zusage, Absage und Warteliste. Alles andere folgt den Einstellungen im Konto (neu: „Neue Ausschreibungen“, „Freigegebene Seminare“).
  - Jede gesendete Mail steht im Verlauf („Mail gesendet: … (n Empfänger)“), ohne Adressen.
  - Von Hand auslösbar am Seminar (Kasten „Mitteilungen“): Rückfrage an die Schule (Geschäftsstelle), Briefing, Onboarding-Termin mit Einladung, neue Termine melden (Koordination).
  - „Vorbereitet“ setzt jetzt einen Onboarding-Termin voraus.
  - Die Hinweise der Schule an die Koordination (Anfrage, Bestätigung, Rückmeldung) laufen unverändert an die Koordination, nicht an die Geschäftsstelle.
- **Phase 5 umgesetzt** (nur syntaxgeprüft, Tests in der CI):
  - **Seminarleitung** (`Teamende › Meine Leitung`): sieht nur das eigene Seminar, bestätigt die Durchführung (erst nach dem letzten Termin), lädt Verträge, Ergebnisbericht, Teilnehmendenzahlen und Sonstiges hoch und gibt ab (nur mit Ergebnisbericht).
  - **Stichtag** für Verträge und Bericht: letzter Termin plus 14 Tage (`PORTAL_SEMINAR_REPORT_DAYS`). Befehl `seminars:remind-reports` (täglich): Erinnerung an die Seminarleitung drei Tage vorher, Hinweis an die Koordination bei Überfälligkeit, je einmal.
  - **Koordination**: Kasten „Bericht und Ergebnislink“ am Seminar: Link zum Ergebnisordner mit Ablaufdatum (mindestens ein Jahr nach dem letzten Termin) und „Bericht an die Schule senden“ (Mail mit Link und Bitte um Rückmeldung im Portal).
  - Beim Abschluss löst das Portal das Ereignis `SeminarCompleted` aus, an dem später die Abrechnung andockt.
  - Nicht übernommen: Der Abschluss verlangt den gesendeten Bericht nicht als Pflichtpunkt der Checkliste (die vorhandenen Abschlusstests hätten sonst alle angepasst werden müssen). Das lässt sich mit einer Zeile nachholen.
- **Phase 7 umgesetzt** (nur syntaxgeprüft, Tests in der CI):
  - Befehl `seminars:prune-requests` (täglich): löscht abgelehnte und abgesagte Anfragen **ohne Vertrag** ein Jahr nach dem Abschluss (`PORTAL_SEMINAR_REQUEST_YEARS`), samt Dateien. Seminare mit unterschriebenem Schulvertrag, Teamverträgen oder Rechnung und Vertrag als Dokument bleiben.
  - **Ergebnislink**: Standard zwei Jahre ab Seminarende (`PORTAL_SEMINAR_RESULTS_YEARS`), nie unter einem Jahr; „Verlängern“ am Seminar (nie verkürzen); Befehl `seminars:results-expiry` (täglich) weist die Koordination 30 Tage vor dem Ablauf einmal hin (`PORTAL_SEMINAR_RESULTS_NOTICE_DAYS`). Gelöscht wird nichts von selbst.
  - Verträge und Belege mit Geldbezug: weiter nur nach den Fristen der Dokumente (`retention_years`, Standard zehn Jahre). Die Fristen sind mit der Steuerberatung zu klären.
  - Auf der Seminarliste der Geschäftsstelle gibt es den Link „Teamverträge“ zum Vertragsabschnitt am Seminar.
- **Phase 5a umgesetzt** (nur syntaxgeprüft, Tests in der CI): **Online-Druckplaner** der Seminarmappe (`/seminare/{id}/druckplaner`). Je Dokument: drucken ja/nein, welche Seiten („alle“ oder „1-3, 5“), Exemplare (1 bis 500), Schwarzweiß oder Farbe, Seiten pro Blatt (1, 2, 4), beidseitig. Das Portal rechnet Seiten und Blätter je Farbe und gesamt, speichert den Plan am Seminar und erzeugt eine **Druckliste** (Druckansicht und PDF). Die Seitenzahl liest es bei PDF-Dateien selbst aus (ohne Bibliothek, Spalte `pages`); bei anderen Dateien oder ungewöhnlichen PDFs trägt man sie von Hand ein. Zugang: Koordination und Geschäftsstelle (alle Dokumente), Seminarleitung und bestätigtes Team (ohne Verträge, Kostenvoranschläge und Rechnungen). Die ZIP-Mappe bleibt daneben.
- Noch **nicht** umgesetzt: Phase 6 (Nextcloud-Anbindung mit Ordnern, Rechten und Ergebnislink).
