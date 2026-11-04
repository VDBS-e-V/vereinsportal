# Entscheidungen und ihre Folgen für den Plan

Antworten des Vorstands auf die 28 Fragen aus `01_OFFENE_FRAGEN.md` (Stand: Antwort im Chat) und was sich dadurch im Plan ändert.

| Nr. | Antwort | Folge |
|---|---|---|
| 1 | Strato Webhosting, Document-Root frei wählbar | Paket A: **Variante 1** (Document-Root auf `public/`). Variante 2 entfällt als Standard, bleibt als Notiz. |
| 2 | Cron über **externen Dienst**, frei konfigurierbar, **öffentliche URL mit Schlüssel** | Paket A6: Web-Cron `GET /cron/{key}` ist der **Hauptweg**. Schlüssel in der `.env`, in der Systemsteuerung anzeigbar und erneuerbar, Drosselung, Sperre gegen Parallelläufe, Aufrufprotokoll (Zeit, Dauer, Zahl der Aufträge). PHP-Cron nur Alternative. |
| 3 | SMTP möglich, Limit unbekannt aber hoch | A6: `MAILINGS_PER_HOUR` bleibt einstellbar, Standard hoch (2 000); Messung der tatsächlichen Rate in der Zustellqualität. |
| 4 | 100 GB Speicher, große Datenbanken | D/G: Grenzen je Seminar/Antrag großzügig (Standard 500 MB je Seminar), Gesamtbelegung in der Systemsteuerung. |
| 5 | Datenbankexport im Kundenmenü | A8: Sicherung über Kundenmenü, keine PHP-Sicherungsfunktion nötig (entfällt, spart 0,25 ET). |
| 6 | Format `S-JJJJ-NNNN`, auch `V-JJJJ-NNNN` | B wie geplant, Veranstaltungen mit Präfix `V-`. |
| 7 | Stammdaten wie vorgeschlagen | B3 wie geplant. |
| 8 | Kompetenzen wie Teamprofil | C wie geplant. |
| 9 | Honorar **nicht** am Link, **auf der Seite** (nach Zugang) ja | C3: Öffentlicher Teil ohne Honorar; nach Eingabe des persönlichen Zugangs zeigt die Seite das Honorar-Überblick wie im Teamkonto. |
| 10 | Teamkonten zeitnah danach; Bewerbung ohne Konto bleibt möglich. **Import der Mailingliste, jede Person bekommt einen individuellen Zugang mit Code, der sie einer Person zuordnet** | **Neu in C: C0 Import und persönlicher Zugang** (+4 ET). Der persönliche Zugang ersetzt den allgemeinen Seminarlink als Hauptweg (der allgemeine Link bleibt optional). Beim Anlegen der Teamkonten wird der Code zur Kontoaktivierung. |
| 11 | Fristen exakt so | 00_UEBERBLICK 4.2 gilt. |
| 12 | PDF-Download wenn möglich, sonst Druckansicht | D2: dompdf einplanen (Eignung auf Strato prüft ein Test in A3), Fallback Druckansicht. |
| 13 | Fragenkataloge in Ordnung | E wie geplant. |
| 14 | Mindestanzahl individuell, entscheidet die Koordination | E: Mindestanzahl **je Link/Seminar** einstellbar (0 erlaubt). |
| 15 | Anonym; **nur die Koordination liest** und anonymisiert für die Langzeitaufbewahrung | E: `feedback.view` nur Koordination; neue Funktion **Anonymisieren und archivieren** (E5). |
| 16 | Nextcloud und Moodle; **Webinterface für Anbindungen** | F2 wie geplant (Verwaltung im Portal, ohne Konsole). |
| 17 | Technisch einfachste und sinnvollste Lösung | F: Rollen als `groups`-Claim mit stabilen Gruppennamen (`vdbs-koordination`, …), Nextcloud-Gruppenordner hängen an diesen Gruppen. |
| 18 | Nextcloud/Moodle für Mitarbeitende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand; je Rolle eigene Teamordner | F: Standard `allowed_roles` für Nextcloud; Gruppen je Rolle; Ordnerrechte legen Sie in Nextcloud auf die Gruppen. Rolle „Mitarbeitende“: **Rückfrage R3**. |
| 19 | Alles über die Geschäftsstelle; ab 50 € zusätzlich Geschäftsführung oder Vorstand | G: neue Freigabelogik (siehe G2). Rolle „Geschäftsführung“: **Rückfrage R3**. |
| 20 | 30 Cent | G3. |
| 21 | Aktuelles Kalenderjahr + Januar und Februar des Folgejahres | G: Belegfrist-Regel (siehe G2); Härte der Regel: **Rückfrage R5**. |
| 22 | CSV | G5. |
| 23 | Keine SEPA-Datei | G8 entfällt. |
| 24 | Antragsberechtigt: Teamende, Mitarbeiter, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand; Mitglieder nicht | G: `forms.submit` nur für diese Rollen. |
| 25 | Koordination darf Teamende und Schulen anschreiben | H1 wie geplant. |
| 26 | Keine Freigabe für die Koordination | H1: Freigabeschwelle gilt nur für die Geschäftsstelle. |
| 27 | Filter einbauen | H2 wie geplant, Filterliste: **Rückfrage R9**. |
| 28 | Satzungstexte exakt | H3: Platzhalter, Sie liefern die Texte, Einbau ohne Umformulierung. |

## Auswirkung auf den Aufwand
| Paket | vorher | nachher | Grund |
|---|---|---|---|
| A | 9 | 8,75 | Sicherungsfunktion entfällt, Web-Cron wird Hauptweg (+0,5), Variante 2 entfällt (−0,75) |
| C | 8 | 12 | Import und persönlicher Zugang (+4) |
| E | 11 | 11,5 | Anonymisieren/Archivieren (+0,5) |
| G | 12 | 11 | SEPA entfällt, Antragslink über persönlichen Zugang spart Aufwand (−1) |
| gesamt | 63 | **66** | |
