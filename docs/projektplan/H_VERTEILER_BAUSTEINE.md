# Paket H: Verteiler, Rechte-Matrix und Grundbestand an Vorlagen und Textbausteinen

Aufwand: **7,25 ET**. Ziel: Wiederkehrende Empfängerkreise sind als **benannte Verteiler** gespeichert, die Rechte je Rolle sind klar geregelt, und die Geschäftsstelle startet mit einem **umfangreichen Grundbestand** an Vorlagen und Textbausteinen.

## 1 Ausgangslage
- Rundschreiben (Vorstand, Geschäftsstelle) mit Gruppen nach Rolle, „Außer“-Auswahl, Einzelpersonen (Konten, Mitglieder, Schul-Ansprechpersonen, einzelne Adressen), Kontextstart ab Schule und Seminar. Koordination: Mitteilungen an Teamende zu Seminaren (Zielgruppe nach Seminar).
- Mailvorlagen mit Versionsverlauf und Textbausteine mit Mehrfachauswahl und Suche; **keine mitgelieferten Inhalte**, keine Kategorien.
- Rechte: `mailings.members` (Vorstand), `mailings.send` (Geschäftsstelle, Vorstand), `mailings.team` (Koordination), `mail_templates.manage` (Geschäftsstelle, Vorstand).

## 2 Entscheidungen

| Frage | Entscheidung / Annahme |
|---|---|
| Was ist ein Verteiler? | Ein **benannter, gespeicherter Empfängerkreis** aus Gruppen, Ausschlüssen und Filtern. Er speichert die **Regel**, nicht die Adressen; beim Versand wird er neu berechnet. |
| Wer legt an? | `mail_segments.manage` (Geschäftsstelle, Vorstand). Der Vorstand darf Mitglieder-Verteiler anlegen, die Geschäftsstelle nicht. |
| Wer nutzt? | Je Verteiler wählbar: Vorstand, Geschäftsstelle, Koordination (siehe Rechte-Matrix 4). |
| Koordination und Gruppen | Annahme: Die Koordination darf **Teamende, Schulen (Ansprechpersonen), Bildungsreferenten** anschreiben, aber **nicht Mitglieder** und nicht den Vorstand. Bitte bestätigen (Frage 1). |
| Filter | Gewünscht (Antwort 27): Bundesland der Schule, Schulart, Seminar (Teamende bestätigt/beworben), Einarbeitung offen, Rolle, Mitgliedschaft seit/bis (Jahr), Teamprofil vollständig/unvollständig, Kompetenzen, **Importierte Kontakte** (Zugang gesendet/benutzt/nie benutzt, aus Paket C0). Gewünscht außerdem (Antwort R9): **PLZ-Bereich** (Anfang der Postleitzahl, z. B. `10`, `101`), **Altersgruppe** (aus dem Geburtsdatum der Person, Gruppen 18–29, 30–49, 50–64, 65+), **Mitgliedsstatus** (Antrag, Mitglied, beendet, kein Mitglied). Weitere Filter, die ich ergänze (Antwort Q6): Bewerbung in den letzten 12 Monaten / nie beworben, Zugang gesendet aber nie geöffnet, durchgeführte Seminare (mindestens n), Teamprofil vollständig, Einarbeitung abgeschlossen. |
| Grundbestand | Als **Migration/Seeder** (`MailDefaults`), nur wenn die Tabellen leer sind; danach frei änderbar. Nie überschreiben. |

## 3 Datenmodell
**`mail_segments`**: id, name (unique), description, definition json, visible_to json (`board`, `office`, `coordination`), created_by_user_id, updated_by_user_id, timestamps.

Definition:
```json
{
  "groups": ["team", "schools"],
  "exclude": ["board"],
  "filters": {
    "federal_state": ["BE", "BB"],
    "school_type": ["gymnasium"],
    "onboarding": "open",
    "seminar_id": 42,
    "member_since_year": {"from": 2020, "to": null}
  },
  "people": ["u:12", "e:name@beispiel.example"]
}
```
Filter sind UND-verknüpft, Werte innerhalb eines Filters ODER-verknüpft.

**`mail_snippets`** (vorhanden) + `category` string(40), `sort` int. **`mail_templates`** (vorhanden) + `category`, `audience` (`members|team|schools|all`).

## 4 Rechte-Matrix (Entwurf zur Bestätigung)

| Empfängerkreis | Vorstand | Geschäftsstelle | Koordination |
|---|---|---|---|
| Mitglieder | ✓ (Pflichtmitteilung möglich) | – | – |
| Teamende | ✓ | ✓ | ✓ (zu Seminaren und allgemein) |
| Schulen (Konten und Ansprechpersonen) | ✓ | ✓ | ✓ |
| Koordination / Bildungsreferenten | ✓ | ✓ | ✓ |
| Vorstand / Geschäftsstelle | ✓ | ✓ | – |
| Einzelpersonen: Konten | ✓ | ✓ | nur Teamende und Schulen |
| Einzelpersonen: Mitglieder | ✓ | – | – |
| Freigabe nötig | – (gibt frei) | ab 50 Empfängern | **nein** (Antwort 26) |
| Verteiler anlegen/ändern | alle | außer Mitglieder-Verteiler | – (nutzt freigegebene) |

Die Matrix steht als Tabelle in `docs/63_RUNDSCHREIBEN.md` und als **Test**, der jede Zelle prüft (`MailRightsMatrixTest`). Technisch: `MailGroups::GROUPS[…]['ability']` bleibt; die Koordination bekommt `mailings.send` **eingeschränkt** über eine neue Capability `mailings.send_limited` mit eigener Gruppenliste (`MailGroups::allowedFor` berücksichtigt sie).

## 5 Arbeitspakete

### H1 Rechte-Matrix umsetzen (1 ET)
- Capability `mailings.send_limited` (Koordination). `MailGroups::allowedFor` liefert für sie `team`, `schools`, `coordination`, `education`. Das Rundschreiben-Formular für die Koordination: neue Seite im Bereich Koordination `Rundschreiben` (`mailings.coordination.*`) – wiederverwendet `GroupMailingController` mit dem Routenpräfix, wie die Bereiche Vorstand und Geschäftsstelle.
- Absender-Standard `MAILINGS_FROM_NAME_COORDINATION`/`REPLY_TO_COORDINATION`.
- **Keine** Freigabeschwelle für die Koordination (Antwort 26); Freigabe bleibt nur für die Geschäftsstelle ab 50 Empfängern.
- Tests: Matrixtest (alle Zellen), Koordination sieht keine Mitglieder-Gruppe und nicht die Mitglieder-Personensuche, Navigationseintrag.

### H2 Gespeicherte Verteiler (2 ET)
- `SegmentResolver::recipients(Segment, bool $mandatory)` → Liste im Format von `MailRecipients` (Gruppen, Ausschlüsse, Personen wie bisher, **plus Filter**). Filter greifen auf `Partner`, `Membership`, `Onboarding`, `SeminarApplication` zu; jede Filterart ist eine kleine Klasse mit `apply(Collection)`/Query, damit weitere leicht dazukommen.
- Oberfläche (Geschäftsstelle › **Verteiler**): Liste (Name, Beschreibung, Erreichbare jetzt, sichtbar für), Anlegen/Ändern mit denselben Mehrfachauswahlen wie das Rundschreiben plus Filterabschnitt, **Vorschau mit Zahl und Beispielnamen** (erste 10, nur für Berechtigte), Löschen.
- Im Rundschreiben-Formular: neue Mehrfachauswahl **„Verteiler“** (nur sichtbare); die Auswahl wird beim Speichern des Entwurfs als Verweis (`payload.segments`) abgelegt und **beim Versand neu aufgelöst** (Abmeldungen und Sperren von jetzt).
- Tests: Auflösung zum Sendezeitpunkt, Filterkombinationen, Sichtbarkeit je Rolle, gelöschter Verteiler im alten Entwurf (Hinweis statt Fehler), Mitglieder-Verteiler nur durch Vorstand.

### H3 Grundbestand an Vorlagen und Textbausteinen (2 ET)
Mitgeliefert, danach bearbeitbar. Alle Texte in Sie-Form, einfache Sprache, mit Platzhaltern aus den bestehenden Listen. Entwürfe zur Freigabe durch Vorstand/Geschäftsstelle.

**Vorlagen (Kategorie · Zielgruppe):**
1. Einladung zur Mitgliederversammlung · Mitglieder (mit `{versammlung}`, `{termin}`, `{ort}`, `{link}`, `{frist}`, Hinweis Antragsfrist)
2. Erinnerung zur Mitgliederversammlung · Mitglieder
3. Absage/Verlegung eines Termins · alle
4. Willkommen im Verein · Mitglieder
5. Seminar-Bestätigung an die Schule · Schulen (`{seminar}`, `{seminar_nr}`, `{termine}`, `{ort}`)
6. Erinnerung an die Schule (Anmeldung/Rückmeldung bis `{frist}`) · Schulen
7. Team-Ausschreibung (mit `{bewerbungslink}`, `{stichtag}`) · Teamende
8. Team-Bestätigung (mit `{team}`, `{termine}`) · Teamende
9. Absage an Bewerbende (freundlich, mit Verweis auf spätere Seminare) · Teamende
10. Feedback-Bitte Lehrkraft (`{feedback_link_lehrkraft}`) · Schulen
11. Feedback-Bitte Team (`{feedback_link_team}`) · Teamende
12. Einarbeitung: Erinnerung · Teamende
13. Rundmail der Geschäftsstelle (neutral) · alle
14. Einladung Großveranstaltung · Schulen/Lehrkräfte
15. Hinweis zur Datenänderung (Adresse/Kontakt prüfen) · Mitglieder

**Textbausteine (Kategorie):**
- *Anrede & Gruß:* Förmliche Anrede, Kollegiale Anrede (Teamende), Gruß mit Absender, Gruß Geschäftsstelle, Gruß Vorstand.
- *Termine & Orte:* Terminblock (`{termin}`, `{ort}`), Online-Teilnahme (`{link}`), Anreise und Barrierefreiheit, Terminverschiebung.
- *Anmeldung & Fristen:* Anmeldehinweis mit `{frist|bald}`, Antragsfrist Satzung, Rückmeldung bis.
- *Datenschutz & Abmeldung:* Datenschutzhinweis kurz, Abmeldehinweis mit `{abmelden|nicht möglich}`, Kontakt bei Fragen.
- *Kontakt:* Kontaktblock Geschäftsstelle, Kontaktblock Koordination, Kontaktblock Vorstand.
- *Seminare:* Ablaufhinweis für Schulen, Hinweis für Teamende (Treffpunkt/Material), Hinweis zum Kostenvoranschlag, Hinweis auf Feedbackbogen, Hinweis Kostenerstattung (Link zum Antrag).
- *Allgemein:* Danke an Ehrenamtliche, Hinweis „keine Fotos von Teilnehmenden“, Hinweis zu Barrierefreiheit.

Umsetzung: `database/seeders/MailDefaultsSeeder` und Aufruf aus der Einrichtung (Paket A5, Schritt „Standardinhalte“) sowie als Aufgabe `standardinhalte-laden` in der Systemsteuerung (ergänzt nur fehlende Einträge, überschreibt nie).
- Tests: Seeder legt vollständigen Bestand an, zweites Ausführen ändert nichts, Platzhalter aller Vorlagen sind gültig (`MailPlaceholders::unknown` leer), jede Vorlage rendert in der Vorschau.

### H4 Kategorien, Suche, Ordnung (0,5 ET)
- Kategorien für Vorlagen und Bausteine (Auswahl aus fester Liste), Suchfeld und Filter in der Verwaltung, Sortierung, Dublette erkennen (gleicher Name).
- Beim Schreiben: Vorlagenauswahl gruppiert nach Kategorie, Bausteinauswahl hat Kategorie als Suchzusatz.

### H5 Platzhalter erweitern (0,5 ET)
Neue Platzhalter, die die Pakete B, C, E brauchen: `{seminar_nr}`, `{team}`, `{bewerbungslink}`, `{stichtag}`, `{feedback_link_lehrkraft}`, `{feedback_link_team}`, `{antraglink}`. Platzhalter ohne Wert bleiben leer oder nehmen den Standardwert; die Liste im Formular erklärt sie.

### H6 Doku und Anleitungen (im Aufwand)
`docs/63_RUNDSCHREIBEN.md` (Verteiler, Matrix, Grundbestand), `docs/66_RUNDSCHREIBEN_ANLEITUNGEN.md` um „Einen Verteiler anlegen“ und „Vorlage anpassen“ erweitern.

## 6 Abnahme (M5, Teil 2)
1. Verteiler „Teamende Berlin/Brandenburg ohne Einarbeitung“ anlegen: Vorschau zeigt die richtige Zahl.
2. Koordination sendet daran ein Rundschreiben, Freigabe ab 50 greift.
3. Neues Konto im Verteiler erscheint beim nächsten Versand, ohne den Verteiler zu ändern.
4. Grundbestand ist da, jede Vorlage lässt sich in der Vorschau öffnen.

## 7 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| Koordination schreibt an zu viele | Freigabeschwelle, Rechte-Matrix, Protokoll |
| Verteiler veraltet (Filter passen nicht mehr) | Vorschau mit Zahl bei jedem Öffnen, Hinweis bei 0 Empfängern |
| Texte des Grundbestands passen nicht | Entwürfe zur Freigabe, danach frei änderbar, nie überschrieben |
| Filter-Performance | Auflösung in einer Abfrage je Filterart, Grenzen (max. 5 000 Empfänger) |

## 8 Offene Fragen
Siehe `01_OFFENE_FRAGEN.md`: R9 (Filter), R11 (Satzungstexte liefern, Antwort 28: exakt übernehmen).
