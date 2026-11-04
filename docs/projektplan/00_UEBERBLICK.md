# Projektplan Live-Gang: Überblick

Stand: Plan. Ersetzt den Kurzplan in `docs/67_PROJEKTPLAN_LUECKEN.md` im Detail; die Bestandsaufnahme dort gilt weiter. Jedes Paket hat eine eigene Datei in diesem Ordner.

## 1 Entscheidungen (von Ihnen bestätigt)

| Thema | Entscheidung | Folge |
|---|---|---|
| Hosting | **Strato Webhosting**, PHP 8.4, Document-Root frei wählbar, 100 GB, große Datenbanken, SMTP, **Cron über externen Dienst mit öffentlicher URL und Schlüssel**, nur FTP | Paket A: Document-Root auf `public/`, Web-Cron `GET /cron/{key}` als Hauptweg. |
| Login | Das Portal ist **nur Anbieter** (Nextcloud, Moodle), Webinterface für Anbindungen. Keine Anmeldung über Google/Microsoft | Paket F5 entfällt. |
| Teamende-Liste | Die bestehende **Mailingliste wird importiert**; jede Person bekommt einen **persönlichen Zugang mit Code**, der sie eindeutig zuordnet. Teamkonten folgen zeitnah danach | Paket C0. |
| Formulare Geschäftsstelle | **Kostenrückerstattung**, **Fahrtkostenabrechnung**, **Materialbestellung/Beschaffung** | Paket G mit gemeinsamem Ablauf und drei Formulartypen. Honorarabrechnung und Urlaub sind nicht Teil dieser Runde (siehe G, Abschnitt „Später“). |
| Feedbackfragen | **Entwurf durch mich**, Sie prüfen | Fragenkataloge stehen in Paket E (Abschnitt 4) und werden vor dem Bau von der Koordination freigegeben. |

## 2 Pakete und Reihenfolge

| Paket | Inhalt | Datei | Aufwand (ET) | Abhängig von |
|---|---|---|---|---|
| A | Betrieb auf Webhosting ohne Konsole | `A_BETRIEB_WEBHOSTING.md` | 8,75 | – |
| B | Seminarnummer und Stammdaten | `B_SEMINARNUMMER.md` | 3 | A (nur für Livegang) |
| C | Import der Mailingliste, persönlicher Zugang, Teambewerbung, gemeinsame Teaminformation | `C_TEAMBEWERBUNG_LINK.md` | 12 | B |
| D | Sicherung des Seminars (Dokumente, Statistik, Mappe) | `D_SEMINARSICHERUNG.md` | 8 | B |
| E | Digitale Feedbackformulare | `E_FEEDBACK.md` | 11,5 | B, D (Schnappschuss) |
| F | SSO / OAuth als Anbieter | `F_SSO.md` | 6 | A (Schlüssel und Einrichtung) |
| G | Formulare der Geschäftsstelle | `G_FORMULARE_GESCHAEFTSSTELLE.md` | 11 | A, C (persönlicher Zugang für Teamende) |
| H | Verteiler, Rechte-Matrix, Grundbestand Bausteine | `H_VERTEILER_BAUSTEINE.md` | 6 | – |

Summe: **66 ET** (Details der Änderungen in `02_ENTSCHEIDUNGEN.md`), Schätzung ±30 %. Ein Entwicklungstag meint einen Arbeitstag einer Person einschließlich Tests und Doku.

### Empfohlene Reihenfolge und Meilensteine

1. **M1 Live-Fähigkeit** (A): Das Portal läuft auf dem Hosting. Probelauf mit Testdaten, Zugangstest, Sicherungsprobe. Erst danach geht jede weitere Auslieferung über das Paket aus A2.
2. **M2 Seminarkette** (B, C): Nummer, Linkbewerbung, Team-Information.
3. **M3 Sicherung und Feedback** (D, E).
4. **M4 Anbindungen** (F) parallel zu M3 möglich, weil es nur A braucht.
5. **M5 Geschäftsstelle** (G, H). H kann jederzeit vorgezogen werden, es hat keine Abhängigkeit.

```
A ──► B ──► C ──► G
      │      
      ├──► D ──► E
A ──► F
H (frei)
```

## 3 Regeln für jedes Paket (Definition of Done)

1. Code im passenden Modul unter `app/Modules/<Name>`, Routen mit Namenspräfix je Bereich (Navigationstest verlangt: jede Seite hinter dem Login gehört genau einem Bereich).
2. Rechte als Capabilities (`AccessGovernance\Enums\Capability`, `Support\Capabilities`), nie Rollenprüfung im Controller.
3. Protokoll nur mit Art und Zahlen (`AuditEvent`), nie mit Namen, Texten, Adressen oder Beträgen je Person.
4. Personenbezogene Daten haben eine Aufbewahrungsfrist, einen Aufräum-Befehl (`<modul>:prune`), einen Eintrag in der Systemsteuerung (`SystemTasks`) und einen täglichen Planer-Eintrag.
5. Pest-Tests für Rechte (Gast, falsche Rolle, richtige Rolle), Gültigkeit der Eingaben, Grenzfälle, Aufräumen. Kein Test greift auf Netz oder `storage/logs` zu.
6. Doku in `docs/` (Technik) und, wo Menschen damit arbeiten, eine Anleitung in einfacher Sprache.
7. Texte auf Deutsch, Sie-Form; Barrierefreiheit der Formulare (Labels, Fehlerzusammenfassung, Tastatur).
8. Hosting-Verträglichkeit: keine Funktion verlangt Konsole, Worker, Symlinks, Shell-Aufrufe oder mehr als 30 Sekunden pro Anfrage.

## 4 Querschnittsthemen

### 4.1 Rechte und Rollen (neu)
| Capability | Wer | Paket |
|---|---|---|
| `seminars.documents` | Koordination, Geschäftsstelle, Vorstand | D |
| `feedback.manage` | Koordination, Vorstand | E |
| `feedback.view` | **nur Koordination** (liest und anonymisiert) | E |
| `team.invites` | Koordination | C |
| `forms.submit` | Teamende, Mitarbeitende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand (Mitglieder nicht) | G |
| `forms.manage` | Geschäftsstelle | G |
| `forms.approve` | Vorstand | G |
| `oauth.manage` | Administration | F |
| `mail_segments.manage` | Geschäftsstelle, Vorstand | H |

### 4.2 Aufbewahrung (Annahmen, Steuerberatung und Vorstand müssen bestätigen)
| Daten | Frist | Begründung |
|---|---|---|
| Gastbewerbungen Team | 6 Monate nach Seminarende | Kein Vertrag zustande gekommen oder Team abgeschlossen |
| Feedback-Rohantworten | 24 Monate | Auswertung und Qualitätsbericht; Kennzahlen bleiben im Seminar |
| Seminardokumente | 10 Jahre (Abrechnungsunterlagen), Teamlisten 3 Jahre | §147 AO für Buchungsbelege, übrige kürzer |
| Erstattungsanträge und Belege | 10 Jahre ab Ende des Jahres | Buchhaltungsbelege |
| IBAN im Antrag | bis 30 Tage nach Auszahlung, dann gelöscht | Datensparsamkeit |
| Webhook-/Betriebsprotokolle | 90 Tage | wie bisher |

### 4.3 Hosting-Grenzen, die jedes Paket beachtet
- **Zeitlimit:** langlaufende Arbeit (ZIP-Mappe, Sammelexport, Auswertung) in kleinen Schritten oder als Aufträge über den Cron-Aufruf.
- **Speicher:** Uploads liegen im privaten Speicher; Gesamtgrenze je Seminar und je Antrag einstellbar.
- **E-Mail:** Versand über SMTP des Hosters; Stundenlimit beachten (`MAILINGS_PER_MINUTE`, `MAILINGS_BATCH_SIZE` aus A6).
- **Keine Symlinks:** Dateien privat über Controller ausliefern (bereits Muster im Projekt).
- **Erweiterungen:** `zip`, `gd`, `intl`, `fileinfo` prüft die Zustandsprüfung (A3).

### 4.4 Testdaten und Probeläufe
- Paket `database/seeders/DemoSeeder` (neu, in A): Beispielschule, zwei Seminare, Teamende, Formulare. Nur lokal und auf dem Stagingsystem; die Einrichtung auf dem Livesystem legt keine Demodaten an.
- Je Meilenstein ein **Probelauf mit Checkliste** (in jedem Paket unter „Abnahme“).

## 5 Zeitplan (Vorschlag, eine Person)

Annahme: 4 produktive Tage pro Woche, Puffer 20 %. Die Spalte „Woche“ zählt ab Start.

| Woche | Inhalt | Lieferung |
|---|---|---|
| 1–2 | A1 Hoster-Prüfung, A2 Paket, A3 Prüfungen, A4 Layout | Testlauf auf dem Hosting mit leerer Datenbank |
| 3 | A5 Einrichtung, A6 Cron und Warteschlange, A7/A8/A9 | **M1** Live-Fähigkeit |
| 4 | B | Seminarnummer |
| 5–6 | C | **M2** Linkbewerbung, Teaminformation |
| 7–8 | D | Seminarsicherung |
| 9–11 | E | **M3** Feedback |
| 12–13 | F | **M4** SSO |
| 14–16 | G | Formulare der Geschäftsstelle |
| 17–18 | H, Abschlussprobe | **M5** Verteiler und Vorlagen, Gesamtabnahme |

Paralleles Arbeiten zweier Personen verkürzt auf etwa 10–11 Wochen (F und H neben dem Hauptstrang).

## 6 Gesamtabnahme vor dem Livegang
1. Alle Abnahmen der Pakete bestanden.
2. Komplette Pest-Suite grün.
3. Sicherheitsprüfung: keine Konsolenfunktion nötig, `.env`/`storage` nicht erreichbar, `APP_DEBUG=false`, Zwei-Faktor für Administration/Geschäftsstelle/Vorstand aktiv.
4. Datenschutz: Verzeichnis der Verarbeitungstätigkeiten ergänzt, Löschfristen im Betrieb getestet.
5. Probebetrieb mit der Koordination (zwei Wochen) auf dem Livesystem mit Testseminar.
6. Sicherungs- und Wiederherstellungsprobe dokumentiert.
7. Notfallplan: Wartungsmodus, Ansprechperson, Zugang zum Hoster.
