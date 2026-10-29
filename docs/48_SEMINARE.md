# 48 Seminare (Schulseminare und Kostenrechner)

Bereich **Koordination**, Seiten **Seminare** (`/koordination/seminare`) und **Preise** (`/koordination/preise`). Umgesetzt sind die Stufen 1 und 2
aus `docs/47_SEMINARPLANUNG.md` für drei Leistungen: **SV-Tage**, **SV-Coaching** und **SV-Wahl**. SV-Fahrt, Teamende-Bewerbung und das Anmeldesystem
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

**Kostenvoranschlag:** „Berechnen“ zeigt die Vorschau, „Speichern“ legt eine **unveränderliche** Fassung an (Eingaben, Posten, Summe, Preisstand;
`seminar_quotes`). Die Seite „Schreiben drucken“ (`/koordination/seminare/{id}/kostenvoranschlag/{fassung}/druck`) zeigt den Kostenvoranschlag als Brief im Format des
Vereins (Briefkopf mit Logo, Anschrift der Schulleitung, Datum, Tabelle Pos./Posten/Inhalt/Preis, Endsumme, Fußzeile mit Bankverbindung). Über „Drucken oder
als PDF speichern“ des Browsers entsteht ein A4-PDF. Posten und Inhalt (eine Zeile je Aufzählungspunkt) sind im Formular änderbar; leer = Standardtext je
Leistung (`Support\QuoteTexts`). Die internen Einzelposten stehen nur in der Ansicht im Portal, nicht im Schreiben. Briefkopf, Absenderzeile und Fußzeile
stehen in `config/portal.php` unter `letter`, das Logo in `public/images/brand/vdbs-briefkopf.jpg`. Preise sind Nettobeträge ohne Umsatzsteuerhinweis (Annahme, zu klären).

**Preisliste:** `seminar_price_lists` (Beträge in Cent als JSON). Die Migration legt den „Preisstand Oktober 2025“ an. Änderungen erzeugen eine neue Fassung
mit Gültigkeitsbeginn; die zurzeit gültige ist die mit dem jüngsten erreichten Beginn. Alte Kostenvoranschläge behalten ihren Preisstand.
