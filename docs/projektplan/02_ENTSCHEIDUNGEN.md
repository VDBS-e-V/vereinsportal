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

---

# Zweite Runde (Antworten auf R1 bis R14)

| Nr. | Antwort | Folge |
|---|---|---|
| R1 | Einwilligung liegt vor; die Liste liegt **direkt im Mailsystem** | C0: Import aus dem Export des Mailsystems; Einwilligungsbestätigung im Importdialog (Pflichthäkchen, protokolliert). Welches Mailsystem: Rückfrage Q1. |
| R2 | Die Liste enthält **nur E-Mail-Adressen** | C0: Statt `people` eine leichte Tabelle **`team_contacts`** (E-Mail, Name leer). Den **Namen erfragt die Bewerbungsseite einmalig** beim ersten Zugang. Beim Teamkonto wird daraus eine Person. |
| R2a | Zugang per E-Mail, Neuanforderung erlaubt | wie geplant |
| R2b | Zugang **nur für Bewerbungen**, alles andere später über das Konto | C0 und G6: Der Zugang öffnet nur Bewerbung und eigene Bewerbungen. **G6 entfällt**: Anträge (Erstattung, Fahrtkosten) stellen nur Konten. Kontoaktivierung bleibt über den Code möglich. |
| R3 | Neue Rollen **Mitarbeitende** (arbeiten auch außerhalb der Geschäftsstelle, z. B. Schulbibliothek) und **Geschäftsführung** (vom Vorstand mit besonderen Rechten benannt) | Neues **Paket I** (2 ET). Geschäftsführung kann nur der Vorstand vergeben oder entziehen. |
| R4 | Moodle **nur Anmeldung**; Moodle **5.1.3**, Nextcloud **Hub 10 (31.0.11)** | F: Anleitungen für diese Versionen; keine Kurszuordnung. |
| R12 | PHP bis **8.5** wählbar | A: PHP 8.4 oder 8.5, Test auf beiden. `composer.json` (`^8.4.1`) bleibt. |
| R5 | Belegfrist **harte Sperre**, Geschäftsstelle darf überschreiben | G2: Sperre mit Ausnahmeknopf für `forms.manage` (Begründung Pflicht, protokolliert). |
| R6 | 50 € Gesamtsumme; **mehr darf beantragt werden**, braucht dann Bewilligung durch Geschäftsführung oder Vorstand | G: kein Höchstbetrag, nur Freigabestufe; gilt für alle drei Formulare. |
| R7/R8 | Vorstand und Geschäftsstelle lesen das anonymisierte Archiv; Ablauf in Ordnung | E5 wie geplant, Frist 24 Monate. |
| R9 | Filter: **PLZ, Alter, Mitgliedsstatus**, „…“ | H2: zusätzliche Filter (+1 ET): PLZ-Bereich (Anfang der PLZ), Altersgruppe, Mitgliedsstatus (Antrag, Mitglied, beendet, kein Mitglied), plus bisherige Liste. Ergänzende Wünsche: Rückfrage Q6. |
| R10 | Exportspalten: lieber mehr als zu wenige | G5: zwei Exporte mit reichen Spalten (siehe G, Abschnitt 4). |
| R11 | Texte folgen | Platzhalter bis zur Lieferung. |
| R13 | Kostenstellen: langfristig Stelle in der Geschäftsstelle, **aktuell der Kassierer im Vorstand** | G: Tabelle `cost_centers` (pflegbar), und **Übergangsregel**: Solange die Stelle fehlt, hat das Vorstandsamt Kassierer:in `forms.manage` (Capability über Zusatzrolle „Kassenführung“, Q5). |
| R14 | Datenschutz: aktuell ein Vorstandsmitglied, langfristig vielleicht ein Mitarbeiter | Datenschutz-Ansprechperson als **Einstellung** (Name, E-Mail) auf der Datenschutzseite und in Feedback- und Importtexten, keine Rolle im Code. |

## Aufwand nach der zweiten Runde
| Paket | vorher | nachher | Grund |
|---|---|---|---|
| G | 11 | 11 | G6 entfällt (−0,5), Kostenstellen, Übergangsrecht und Ausnahme (+0,5) |
| H | 6 | 7 | zusätzliche Filter (+1) |
| I | – | 2 | neue Rollen Mitarbeitende und Geschäftsführung |
| gesamt | 66 | **69** | |

---

# Dritte Runde (Antworten auf Q1 bis Q7)

| Nr. | Antwort | Folge |
|---|---|---|
| Q1 | Kein Export aus dem Mailsystem; **Sie bekommen eine Vorlage und tragen die Adressen händisch ein** | C0: Download **Vorlage** (CSV, Excel-tauglich, mit Kopfzeile und Beispielzeile) plus **Eingabe im Browser** (Textfeld, eine Adresse pro Zeile, oder Tabelle). Beides führt in dieselbe Vorschau. Die Einwilligung bestätigen Sie weiterhin im Dialog. |
| Q2 | Name wird abgefragt, **nur mit der Option „für die Zukunft speichern“**. Zusätzlich **freiwillige Adresse** (praktisch zum Vorausfüllen von Verträgen) | C3: Beim ersten Zugang fragt die Seite Vor- und Nachname (Pflicht für die Bewerbung) und freiwillig Telefon und Anschrift. Das Häkchen „Für die Zukunft speichern“ entscheidet, ob die Angaben im Kontakt bleiben. Ohne Häkchen gelten sie nur für diese Bewerbung und werden mit ihr gelöscht (Frist 6 Monate nach Seminarende). Vertragsvorlage: Rückfrage Q9. |
| Q3 | Rechte der Geschäftsführung **exakt wie geplant**, mehr später | I: Freigabe ab 50 €, Kostenstellen pflegen, Nextcloud/Moodle, Zwei-Faktor Pflicht. |
| Q4 | Langfristig Bereich Mitarbeitende mit **Arbeitszeiterfassung** u. a. | I3: Bereich zunächst klein; neue Datei `J_AUSBLICK_MITARBEITENDE.md` skizziert den späteren Ausbau (nicht Teil der 69 ET). |
| Q5 | Kassenführung: „er selber / andere aus dem Vorstand“ | G6: Die Kassenführung darf die **Prüfung** ihrer eigenen Anträge selbst vornehmen (Vermerk „Selbstprüfung“ im Verlauf, für alle sichtbar), andere Vorstandsmitglieder können es ebenfalls. Die **Freigabe** über 50 € gibt immer eine andere Person. Freigabe bis 50 € der eigenen Anträge: Rückfrage Q8. |
| Q6 | Keine weiteren Filter, „außer du hast gute Ideen“ | H2: Vorschläge, die ich einbaue (+0,25 ET): *Bewerbung in den letzten 12 Monaten / nie beworben*, *Zugang gesendet aber nie geöffnet*, *durchgeführte Seminare (mindestens n)*, *Teamprofil vollständig*, *Einarbeitung abgeschlossen*. |
| Q7 | **cron-job.org**; Erweiterungen unbekannt | A1: neues Prüfskript `scripts/hostcheck.php` (liegt bereits im Repository), das Sie per FTP hochladen und im Browser öffnen: zeigt PHP-Version, alle Erweiterungen, Grenzwerte, Schreibrechte, HTTPS und testet die Datenbank. Damit ist Q7 in fünf Minuten beantwortet. A6: Anleitung für cron-job.org (URL mit Schlüssel, Takt 1 Minute, Zeitüberschreitung 60 s, Benachrichtigung bei Fehlschlag). |

## Aufwand nach der dritten Runde
| Paket | vorher | nachher | Grund |
|---|---|---|---|
| C | 12 | 12,5 | Vorlage und Eingabe im Browser (+0,25), Adresse und Speicheroption (+0,25) |
| H | 7 | 7,25 | zusätzliche Filter |
| gesamt | 69 | **70** | (Rundung) |
