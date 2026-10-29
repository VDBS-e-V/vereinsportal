# Schulen als Partner (Modul Partners)

Bereich **Koordination**, Seite **Schulen** (`/koordination/schulen`). Pflege der Partnerschulen mit Ansprechperson, Angaben zur Schule,
CSV-Export und CSV-Import. Grundlage ist die generische Tabelle `partners` (Typ `school`; weitere Partnertypen später als Case in `PartnerType`).

## Berechtigungen

`partners.view` und `partners.manage`: **Koordination, Geschäftsstelle und Vorstand** (`AccessGovernance/Support/Capabilities.php`).
Die Administration (technische Rolle) hat sie nicht. Der Bereich Koordination zeigt sich entsprechend für die Rollen Koordination und Geschäftsstelle (und Vorstand).

## Angaben einer Schule

| Feld | Ablage | Hinweis |
|---|---|---|
| Name (Pflicht), Träger/offizieller Name | Spalten `name`, `legal_name` | Name + Ort sind eindeutig (ohne Groß-/Kleinschreibung) |
| Schulart, Bundesland, Schulnummer, Schülerzahl | JSON `attributes` | Schularten: `SchoolType`, Bundesländer: `FederalState` |
| Anschrift, Land | Spalten | Land zweistellig (Standard DE) |
| Ansprechperson (Name, E-Mail, Telefon) | `contact_name`, `contact_email`, `contact_phone` | freie Angaben; die Spalte `contact_person_id` (bekannte Person) ist vorbereitet, aber noch ohne Oberfläche |
| Status | `active` / `inactive` | statt Löschen: auf „Inaktiv“ setzen |
| Notiz | `notes` | bis 2000 Zeichen |

## Seiten

- **Liste:** Kennzahlen (alle, aktiv, inaktiv), Suche (Name, Träger, Ort, PLZ, Ansprechperson), Filter Status, Schulart, Bundesland,
  Sortierung, 20 pro Seite, ganze Zeile klickbar.
- **Details:** Angaben, Ansprechperson, Notizen, Angelegt/geändert von, **Verlauf** aus dem Protokoll (nur mit `audit.view`).
- **Anlegen/Bearbeiten/Löschen** (Dialog; bei bloßer Inaktivität den Status nutzen).
- **CSV-Export** der aktuellen Filter (UTF-8 mit Kennzeichen, Semikolon, Zellen mit `= + - @` entschärft).

## CSV-Import

Unter „CSV importieren“ mit Vorlage zum Download. Spalten: Name (Pflicht), Träger, Schulart, Bundesland, Schulnummer, Straße, Hausnummer, PLZ, Ort,
Ansprechperson, E-Mail, Telefon, Schülerzahl, Status, Notiz (Überschriften auch als Synonyme, z. B. `Schule`, `Strasse`, `Postleitzahl`).

- **Alles oder nichts:** Enthält die Datei fehlerhafte Zeilen, wird nichts angelegt; jede fehlerhafte Zeile wird mit Nummer genannt (die ersten 50 werden angezeigt).
- Schulart und Bundesland werden als Bezeichnung oder Schlüssel erkannt („Gymnasium“, „Sachsen“, „SN“); Status `aktiv`/`inaktiv` (leer = aktiv).
- **Bestehende Schulen** (gleicher Name am selben Ort) werden übersprungen, nicht überschrieben; Dubletten innerhalb der Datei gelten als Fehler.
- Trennzeichen Semikolon oder Komma, UTF-8 (mit/ohne BOM) oder Windows-1252; höchstens 1000 Zeilen und 2 MB; begrenzt auf 10 Importe je 10 Minuten.

## Protokoll

Ereignisse „Schule angelegt/geändert (nur Feldnamen)/gelöscht“ und „Schulen importiert (Anzahl)“ im Protokoll (`docs/42_PROTOKOLL.md`), Filter
`subject=partner:ID`. Ansprechpersonen und Notizen stehen nie im Protokoll.

## Noch offen

- Verknüpfung der Ansprechperson mit einer Person/einem Konto und der Rolle für Schulen (Bereich „Schulen“ im Portal bleibt leer, bis es Schulkonten gibt).
- Weitere Partnertypen (Sponsoren, andere Vereine), Dokumente und Verträge je Partner.

Tests: `tests/Feature/Partners/PartnersTest.php`. Keine neue Migration.
