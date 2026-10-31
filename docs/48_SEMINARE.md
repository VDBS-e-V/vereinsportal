# 48 Seminare (Schulseminare und Kostenrechner)

Bereich **Koordination**, Seiten **Seminare** (`/koordination/seminare`) und **Preise** (`/koordination/preise`). Umgesetzt sind die Stufen 1 und 2
aus `docs/47_SEMINARPLANUNG.md` für vier Produkte: **SV-Tage**, **SV-Coaching**, **SV-Wahl** und **Begleitung GSV-Sitzungen**. SV-Fahrt, das Anmeldesystem
für Großveranstaltungen folgen später.

## Rechte

| Berechtigung | Rollen | Wirkung |
|---|---|---|
| `seminars.view` | Koordination, Geschäftsstelle, Vorstand | Seminare, Kostenvoranschläge und Preisliste ansehen |
| `seminars.manage` | Koordination, Geschäftsstelle, Vorstand | anlegen, ändern, löschen, Status, Termine, Notizen, Kostenvoranschläge |
| `seminars.prices` | Koordination, Vorstand | neue Fassung der Preisliste anlegen |

## Seminar

Felder: Titel, Leistung, Schule (Pflicht, aus `partners`), Klassenstufe, erwartete Teilnehmende, Notiz. Auf der Seite einer Schule führt
„Seminar anlegen“ mit vorgewählter Schule hierher. Eine Schule mit Seminaren lässt sich nicht löschen (Status stattdessen).

**Status:** Anfrage → In Planung → Terminiert → Bestätigt → Durchgeführt → Abgeschlossen, jederzeit Abgesagt (danach wieder Anfrage). Regeln:
„Terminiert“ und „Bestätigt“ brauchen mindestens einen Termin, „Bestätigt“ zusätzlich einen Kostenvoranschlag. Löschen nur bei Anfrage oder Abgesagt.

**Termine** (Einheiten): Datum, Beginn, Ende, Ort (Vorschlag: die Schule), Hinweis. **Verlauf:** jede Änderung (Anlegen, Änderung mit Feldnamen, Status,
Termine, Kostenvoranschlag, Notiz) steht mit Person und Zeit am Seminar (Tabelle `seminar_events`). Das allgemeine Protokoll bekommt nur die
Ereignisse `seminar.created/updated/deleted/status_changed/quote_created/prices_changed`, ohne Inhalte.

## Kostenrechner

Ein Rechner für alle Leistungen (Logik des Blatts „SV-Coaching“ der Preisberechnungstabelle), Klasse `Support\QuoteCalculator`. Beträge in Cent.

| Posten | Menge × Einzelpreis (je Tag × Tage, außer Vereinspauschale) |
|---|---|
| Honorar der Teamenden | Teamende × Staffel nach Stunden pro Tag (unter 2,5 h 100 €, unter 4,5 h 135 €, unter 6 h 170 €, ab 6 h 200 €) |
| Anfahrtspauschale | Teamende × 10 € (innerhalb ABC) oder 45 € |
| Seminarleitung | Anzahl × 30 € (unter 4 h) oder 50 € (ab 4 h) |
| Orgapauschale | 20 € |
| Vereinspauschale | 65 € einmalig, entfällt bei „ohne Vereinspauschale“ |
| Materialpauschale | Menge × 32 € (bis 25 TN) |
| Materialzuschlag | je angefangene 5 TN ab 25 × 4 € |
| Material An- und Abfahrt | Fahrten × 35 € (ABC) oder 105 € |
| Pinnwand | Anzahl × 41 € |

Voreinstellungen: SV-Tage 3 Tage, 3 Teamende, 6 h, 1 Seminarleitung, 45 TN, Material 1; SV-Coaching 2 Treffen, 3 Teamende, 6 h, 30 TN, Material 0,3;
SV-Wahl 1 Treffen, 2 Teamende, 2 h, 20 TN, Material 0,3. Ergebnisse mit diesen Werten: 2.309,00 €, 1.392,20 € und 314,60 € (wie in der Excel-Tabelle).

Abweichung zur Excel-Tabelle: Der Materialzuschlag rechnet „je angefangene 5 TN“ (aufgerundet); Excel rechnet bruchteilig (27 TN = 0,4 × 4 €).
Bei vollen Fünfern (30, 35, 45 TN …) ist das Ergebnis gleich.

**Kostenvoranschlag (KVA)** (`/koordination/kostenvoranschlaege`): gehört zu einer **Schule** und besteht aus mehreren **Positionen**. Jede Position ist ein
**Produkt mit Stückzahl** (SV-Tage: Tage, SV-Coaching und SV-Wahl: Treffen, Begleitung GSV-Sitzungen: Sitzungen) und wird einzeln berechnet; die
Vereinspauschale (65 €) fällt je Position an (abschaltbar je Position). Das Seminar ist nur eine optionale Verknüpfung (nötig für den Status „Bestätigt“);
„Kostenvoranschlag berechnen“ am Seminar öffnet das Formular mit dem Produkt des Seminars. Jede Position ist ein einklappbarer Bereich (Kopfzeile: Produkt und Stückzahl, z. B. „SV-Tage – 3 Tage“; bei einer einzigen Position und bei Fehlern offen). Weitere Positionen mit „Weitere Position hinzufügen“, beim
Wechsel des Produkts übernimmt die Zeile dessen Voreinstellungen (`resources/js/quote-form.js`). „Berechnen“ zeigt die Vorschau, „Speichern“ legt den KVA
fest (Positionen mit Eingaben, Posten, Summe, Preisstand; Tabelle `seminar_quotes`, Spalte `positions` als JSON).
**Änderungen und Versionsverlauf:** Ein bestehender KVA lässt sich mit „Ändern“ bearbeiten (Positionen, Notiz zur Änderung; Schule und Seminar bleiben). Die Positionen werden mit der
*aktuellen* Preisliste und den aktuellen Texten neu berechnet; weicht das Ergebnis ab, entsteht eine neue Fassung, sonst nichts. Alle Fassungen stehen als unveränderliche
Schnappschüsse in `seminar_quote_versions` (Fassung, Datum, Person, Endsumme, Notiz, Preisstand). Auf der KVA-Seite zeigt der Versionsverlauf alle Fassungen; frühere lassen
sich ansehen und drucken (`?fassung=N`), und „Daraus neue Fassung machen“ startet eine Änderung von einer früheren Fassung aus. Das Datum im Brief ist das der angezeigten
Fassung. Ein Löschen entfernt den KVA mit allen Fassungen. „Als Vorlage verwenden“ kopiert
einen KVA in ein neues Formular, Löschen ist mit Protokolleintrag möglich (nicht beim einzigen KVA eines bestätigten Seminars). Eine Schule mit KVAs lässt sich nicht löschen.

Die Seite „Schreiben drucken“ (`/koordination/kostenvoranschlaege/{id}/druck`) zeigt den KVA als Brief im Format des Vereins (Briefkopf mit Logo, Anschrift der
Schulleitung, Datum, Tabelle Pos./Posten/Inhalt/Preis mit einer Zeile je Position, Endsumme, Fußzeile mit Bankverbindung). Über „Drucken oder als PDF
speichern“ des Browsers entsteht ein A4-PDF. Titel und Standardbeschreibung je Produkt sind **globale Werte** mit Platzhaltern (Seite „Texte“, `/koordination/texte`, Tabelle
`seminar_product_texts`, Klasse `Support\QuoteTexts`; ändern darf mit `seminars.prices`). Im Rechner lässt sich je Position nur eine **Ergänzung** („Weitere
Eigenschaften und Beschreibung“, eine Zeile je Punkt) hinzufügen. Beim Speichern werden die Texte mit den Werten der Position gefüllt und im KVA festgehalten, spätere
Änderungen der Texte betreffen alte KVAs nicht. Platzhalter: `{stueckzahl}`, `{einheit}`, `{teamende}`, `{stunden}`, `{gruppe}`, `{leitung}`, `{produkt}`; unbekannte werden abgelehnt. Die internen Einzelposten stehen nur in der Ansicht im Portal, nicht im Schreiben. Briefkopf, Absenderzeile und Fußzeile stehen in
`config/portal.php` unter `letter`, das Logo in `public/images/brand/vdbs-briefkopf.jpg`. Preise sind Nettobeträge ohne Umsatzsteuerhinweis (Annahme, zu klären).
Die Voreinstellungen von „Begleitung GSV-Sitzungen“ (4 Sitzungen, 1 Teamende, 3 Stunden) sind eine Annahme und lassen sich je Position ändern.

**Preisliste:** `seminar_price_lists` (Beträge in Cent als JSON). Die Migration legt den „Preisstand Oktober 2025“ an. Änderungen erzeugen eine neue Fassung
mit Gültigkeitsbeginn; die zurzeit gültige ist die mit dem jüngsten erreichten Beginn. Alte Kostenvoranschläge behalten ihren Preisstand.

## Teamende bekunden Interesse und die Koordination stellt das Team zusammen (Stufe 3)

Bereich **Teamende**, Seiten **Seminare** (`/teamende/seminare`) und **Profil** (`/teamende/profil`, Berechtigung `seminars.apply` für die Rolle Teamende). Umsetzung von Abschnitt 7 aus `docs/47_SEMINARPLANUNG.md` nach folgendem Ablauf:

1. **Freigabe:** Die Koordination trägt am Seminar (Abschnitt „Team“) **Bedarf**, **Stichtag** und die **gesuchten Kompetenzen** ein. Der Stichtag ist Pflicht, sobald es einen Bedarf gibt. Das Seminar muss „Terminiert“ sein.
2. **Interesse bekunden:** Teamende sehen Seminare mit offenem Stichtag (Titel, Leistung, Stadt, Termine, Stichtag, gesuchte Kompetenzen, Honorar-Überblick aus dem Kostenvoranschlag) und bekunden **Interesse** (optional mit Nachricht und dem Hinweis, dass sie auch die Seminarleitung übernehmen würden). Bis zum Stichtag (einschließlich) lässt sich das Interesse zurückziehen und erneut bekunden. Danach geht nichts mehr, und das Seminar verschwindet aus der Liste der offenen Seminare.
3. **Stichtag vorbei:** Die Koordination bekommt **einmal je Stichtag** eine E-Mail (`php artisan seminars:team-deadlines`, täglich geplant; ohne Namen). Ein neuer Stichtag löst die Meldung erneut aus. Vor dem Stichtag lässt sich nicht entscheiden; wer früher sichten will, setzt den Stichtag auf heute oder früher.
4. **Sichten und Team zusammenstellen:** Die Tabelle „Interessenbekundungen“ zeigt je Person **Alter** (aus dem Geburtsdatum der Stammdaten), **Geschlecht** (freiwillige Selbstangabe), **Kompetenzen** (gesuchte hervorgehoben), **Erfahrung** (Zahl der durchgeführten Seminare, in denen die Person schon im Team war, Erfahrung in der Seminarleitung, Freitext), Nachricht, **Überschneidungen** mit ihren bestätigten Seminaren, eine **interne Notiz** und die Aktionen „Ins Team“ bzw. „Nicht berücksichtigen“. Darunter fasst „Zusammensetzung des Teams“ zusammen: Größe, Geschlechter, Durchschnittsalter, Zahl mit Interesse oder Erfahrung in der Seminarleitung und welche gesuchten Kompetenzen abgedeckt sind oder fehlen. Alter und Geschlecht sehen nur Personen mit `seminars.manage`.
5. **Entscheidung:** „Ins Team“ geht nur, solange der Bedarf nicht gedeckt ist. Die Person bekommt eine E-Mail (im Team: mit Schule und Terminen; nicht berücksichtigt: nur Titel und ein Hinweis auf die Kriterien). Ist das Team vollständig, weist die Seite darauf hin, das Seminar auf „Bestätigt“ zu setzen. Wird ein Seminar **abgesagt**, erfahren es alle mit Interesse und alle im Team per E-Mail.
6. **Kalender:** Teamende laden ihre Termine als Kalenderdatei (`/teamende/seminare/kalender.ics`) herunter.

**Profil der Teamenden:** Kompetenzen (Katalog in `config/portal.php` unter `team.competences`), Erfahrungen, Erfahrung in der Seminarleitung und die **freiwillige** Angabe zum Geschlecht (weiblich, männlich, divers oder keine Angabe). Sichtbar nur für die Person selbst und die Koordination, nur für die Teamzusammenstellung. Der Seminarbereich weist Teamende auf ein leeres Profil hin.

**Verlauf und Protokoll:** Interesse, Rückzug, Aufnahme und Nicht-Berücksichtigung stehen mit Namen im Verlauf des Seminars (nur für die Koordination sichtbar). Das allgemeine Protokoll bekommt `seminar.team_decided` nur mit Seminar und Entscheidung, ohne Namen; Profil- und Notizinhalte stehen nie darin.

Tabellen: `seminar_applications` (Seminar, Konto, Stand, Nachricht, Leitung, interne Notiz, Entscheidung), `team_profiles`, Spalten `seminars.team_needed/application_deadline/team_competences/deadline_notified_at`.

## Erinnerungen zur Einarbeitung

`php artisan onboarding:remind-team` (täglich geplant) schickt Teamende mit offener Einarbeitung eine E-Mail, **14 Tage nach Beginn**, dann alle **14 Tage**, **höchstens dreimal**
(`portal.onboarding.reminder`). Die Mail nennt nur die Zahl der offenen Schritte, nie welche. `--dry-run` zählt nur. Abgeschlossene Einarbeitungen und Konten ohne Teamende-Rolle bekommen nichts.
