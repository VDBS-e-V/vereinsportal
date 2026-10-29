# Schulen als Partner (Modul Partners)

Bereich **Koordination**, Seite **Schulen** (`/koordination/schulen`). Pflege der Partnerschulen mit Ansprechperson, Angaben zur Schule,
CSV-Export und CSV-Import. Grundlage ist die generische Tabelle `partners` (Typ `school`; weitere Partnertypen später als Case in `PartnerType`).

## Berechtigungen

`partners.view` und `partners.manage`: **Koordination, Geschäftsstelle und Vorstand** (`AccessGovernance/Support/Capabilities.php`).
Die Administration (technische Rolle) hat sie nicht. Der Bereich Koordination zeigt sich entsprechend für die Rollen Koordination und Geschäftsstelle (und Vorstand).

## Interne Kennung

Jede Schule wird intern über **Bundesland-Kürzel + Schulnummer** geführt, z. B. **BE/08K06** (Berlin, Schulnummer 08K06). Die Kennung ist
eindeutig (dieselbe Schulnummer darf in einem anderen Bundesland vorkommen). Die Schulnummer besteht aus Ziffern, Buchstaben und Bindestrichen
(wird groß gespeichert, kein Schrägstrich); sie braucht das Bundesland. Die Kennung steht in Liste, Details, Export und ist suchbar
(„BE/08K06“ oder nur „08K06“).

## Angaben einer Schule

| Feld | Ablage | Hinweis |
|---|---|---|
| Name (Pflicht), Träger/offizieller Name | Spalten `name`, `legal_name` | Name + Ort sind eindeutig (ohne Groß-/Kleinschreibung) |
| Bundesland, Schulnummer (→ interne Kennung), Schulart, Schülerzahl | JSON `attributes` | Schularten: `SchoolType`, Bundesländer: `FederalState` |
| Anschrift, Land | Spalten | Land zweistellig (Standard DE) |
| **Ansprechpersonen** (bis zu 5: Name, Funktion, E-Mail, Telefon) | Tabelle `partner_contacts` | eine ist die **Hauptansprechperson**; optional mit einer bekannten Person verknüpft (`person_id`, noch ohne Oberfläche, bleibt beim Bearbeiten erhalten) |
| Status | `active` / `inactive` | statt Löschen: auf „Inaktiv“ setzen |
| Notiz | `notes` | bis 2000 Zeichen |

Die bisherigen Einzelfelder (`contact_*`) wurden von der Migration `2026_11_03_000001_create_partner_contacts_table` als Hauptansprechperson
übernommen und entfernt. Im Formular stehen fünf Zeilen; leere Zeilen werden ignoriert, zum Entfernen die Zeile leeren.

## Seiten

- **Liste:** Kennzahlen (alle, aktiv, inaktiv), Suche (Name, Kennung, Schulnummer, Träger, Ort, PLZ, alle Ansprechpersonen), Filter Status, Schulart, Bundesland,
  Sortierung, 20 pro Seite, ganze Zeile klickbar.
- **Details:** Angaben mit interner Kennung, alle Ansprechpersonen, Notizen, Angelegt/geändert von, **Verlauf** aus dem Protokoll (nur mit `audit.view`).
- **Anlegen/Bearbeiten/Löschen** (Dialog; bei bloßer Inaktivität den Status nutzen).
- **CSV-Export** der aktuellen Filter (UTF-8 mit Kennzeichen, Semikolon, Zellen mit `= + - @` entschärft).

## CSV-Import

Unter „CSV importieren“ mit Vorlage zum Download. Spalten: Name (Pflicht), Träger, **Interne Kennung** (BE/08K06; füllt Bundesland und Schulnummer), Schulart, Bundesland, Schulnummer, Straße,
Hausnummer, PLZ, Ort, Ansprechperson, Funktion, E-Mail, Telefon, Schülerzahl, Status, Notiz (Überschriften auch als Synonyme, z. B. `Schule`,
`Strasse`, `Postleitzahl`). **Weitere Ansprechpersonen** stehen in Spaltengruppen mit Zusatz „2“ bis „5“ (`Ansprechperson 2`, `Funktion 2`, `E-Mail 2`, `Telefon 2`); der Export schreibt so viele Gruppen, wie die Schulen Personen haben (mindestens 2).

- **Alles oder nichts:** Enthält die Datei fehlerhafte Zeilen, wird nichts angelegt; jede fehlerhafte Zeile wird mit Nummer genannt (die ersten 50 werden angezeigt).
- Schulart und Bundesland werden als Bezeichnung oder Schlüssel erkannt („Gymnasium“, „Sachsen“, „SN“); Status `aktiv`/`inaktiv` (leer = aktiv).
- **Bestehende Schulen** (gleicher Name am selben Ort) werden übersprungen, nicht überschrieben; Dubletten innerhalb der Datei gelten als Fehler.
- Trennzeichen Semikolon oder Komma, UTF-8 (mit/ohne BOM) oder Windows-1252; höchstens 1000 Zeilen und 2 MB; begrenzt auf 10 Importe je 10 Minuten.

## Protokoll

Ereignisse „Schule angelegt/geändert (nur Feldnamen)/gelöscht“ und „Schulen importiert (Anzahl)“ im Protokoll (`docs/42_PROTOKOLL.md`), Filter
`subject=partner:ID`. Ansprechpersonen (nur der Hinweis „ansprechpersonen“ als geänderter Bereich) und Notizen stehen nie im Protokoll.

## Noch offen

- Oberfläche zur Verknüpfung einer Ansprechperson mit einer Person/einem Konto und die Rolle für Schulen (Bereich „Schulen“ im Portal bleibt leer, bis es Schulkonten gibt).
- Weitere Partnertypen (Sponsoren, andere Vereine), Dokumente und Verträge je Partner.

Tests: `tests/Feature/Partners/PartnersTest.php`. Migration: `php artisan migrate`.
