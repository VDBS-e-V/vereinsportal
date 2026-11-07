# Anträge (Paket G, Teil 1: Ablauf und Kostenrückerstattung)

## Wo Anträge gestellt werden
Anträge sind kein eigener Bereich, sondern eine Seite „Anträge“ in dem Bereich, zu dem die Person gehört. Jeder Bereich hat angepasste Formulare:

| Bereich | Wer | Formulare | Besonderheiten |
|---|---|---|---|
| **Teamende** | Rolle Teamende (`forms.submit_team`) | Kostenrückerstattung, Fahrtkostenabrechnung | Seminar wählbar, keine Kostenstelle |
| **Mitarbeitende** | Mitarbeitende, Geschäftsführung, Kassenführung (`forms.submit`) | Kostenrückerstattung, Fahrtkostenabrechnung, Materialbestellung | Seminar und Kostenstelle wählbar |
| **Mitglieder** | Vereinsmitglieder (`forms.submit_member`) | Kostenrückerstattung, Fahrtkostenabrechnung | ohne Seminar und Kostenstelle |

Jeder Antrag merkt sich seinen Bereich; ein Antrag aus Teamende erscheint nicht unter Mitglieder. Wer mehrere Rollen hat (zum Beispiel Teamende und Mitglied), findet in jedem Bereich seine Anträge. Koordination, Bildungsreferenten, Geschäftsstelle und Vorstand haben keine eigene Antragsseite; wer Auslagen abrechnen will, braucht die Rolle Mitarbeitende oder Mitglied.

Bearbeitung in der **Geschäftsstelle › Anträge**:

| Recht | Rollen | Bedeutung |
|---|---|---|
| `forms.view` | Geschäftsstelle, Vorstand, Geschäftsführung, Kassenführung | Eingang und Prüfseite sehen |
| `forms.manage` | Geschäftsstelle, Vorstand, Kassenführung | prüfen, Rückfrage, Ausnahme Belegfrist, Auszahlung vermerken, Sammelliste, Kostenstellen, Einstellungen |
| `forms.approve` | Vorstand, Geschäftsführung | Freigabe über der Grenze |

## Ablauf
Entwurf → eingereicht → in Prüfung → freigegeben → ausgezahlt; dazwischen Rückfrage (zurück an die Antragstellenden) oder Ablehnung mit Begründung.
- **Freigabe:** Bis zur Grenze (Standard 50 €, einstellbar) gibt die Geschäftsstelle allein frei. Darüber prüft die Geschäftsstelle und eine **andere** Person mit Freigaberecht gibt frei (Geschäftsführung oder Vorstand). Es gibt keinen Höchstbetrag.
- **Nie der eigene Antrag:** Niemand gibt einen eigenen Antrag frei. Prüfen darf nur die Kassenführung ihren eigenen Antrag (im Verlauf steht „Selbstprüfung“).
- **Belegfrist:** Belege aus dem Jahr J können bis Ende Februar J+1 eingereicht werden. Danach gesperrt; die Geschäftsstelle schaltet je Antrag eine Ausnahme mit Begründung frei (im Verlauf und Protokoll).
- **Belege:** PDF, JPEG, PNG, je Datei 15 MB, je Antrag 100 MB, privat gespeichert. Zu jeder Position gehört ein Beleg.
- **IBAN:** Prüfsumme (mod 97), verschlüsselt gespeichert; sichtbar nur für `forms.manage` und in der Sammelliste.
- **Nummern:** `E-JJJJ-NNNN` (Erstattung), später `F-` (Fahrtkosten) und `M-` (Material).
- **Mails:** An die Geschäftsstelle bei neuem Antrag, an Freigebende wenn eine Freigabe nötig ist, an die Antragstellenden bei Rückfrage, Freigabe, Ablehnung, Auszahlung. Nur Nummer, Art und kurzer Text, keine Beträge.
- **Protokoll:** Nummer, Art, Aktion – keine Beträge, keine IBAN.

## Sammelliste (Auszahlung)
Geschäftsstelle › Anträge: freigegebene Anträge ankreuzen (Mehrfachauswahl, alle auswählen) → „Sammelliste herunterladen“. CSV mit `;`, Dezimalkomma, UTF-8 mit BOM, Formelschutz; enthält die IBAN. Das Portal überweist nichts; nach der Überweisung vermerken Sie „Als ausgezahlt“.

## Einstellungen
Geschäftsstelle › Anträge › Einstellungen: Freigabegrenze, Kilometersatz, Warnung ab Kilometern. Kostenstellen unter „Kostenstellen“. `FORMS_ENABLED_TYPES` (Standard `expense`) schaltet Arten frei.

## Noch offen
Fahrtkostenabrechnung (G3), Materialbestellung (G4), Buchungsliste und Jahresübersicht (G5 Rest), Aufbewahrung und Löschen (G7), Aufgabenübersicht.

## Teil 2: Fahrtkosten, Bestellung, Exporte, Aufbewahrung
- **Fahrtkostenabrechnung** (`F-…`): je Fahrt Datum, Verkehrsmittel (PKW, Bahn, Nahverkehr, Mitfahrt), Start, Ziel, Kilometer, Hin-/Rückfahrt (doppelt), Fahrpreis, Parkgebühr. **Der Server rechnet**: PKW = Kilometer × Satz (Standard 30 Cent, Einstellung), sonst der Fahrpreis, plus Parkgebühr. Der Satz wird je Fahrt festgehalten, spätere Änderungen betreffen nur neue Fahrten. Belege für Fahrkarten und Parkgebühren sind Pflicht, die Kilometerpauschale braucht keinen. Warnung (nicht blockierend) ab 1 000 km je Fahrt (Einstellung).
- **Materialbestellung** (`M-…`): Antrag **vor** dem Kauf mit Artikeln (Menge × Einzelpreis brutto, Lieferant, Link), Dringlichkeit, Wunschtermin, Lieferadresse; keine Bankdaten. Gleiche Freigaberegeln. Danach trägt die Geschäftsstelle ein: **bestellt**, **geliefert**, Rechnung anhängen, **abschließen** (nur mit Rechnung). Bestellungen werden nicht ausgezahlt und stehen nicht in der Sammelliste.
- **Exporte** (Geschäftsstelle › Anträge): Sammelliste (Auszahlung, nur Erstattung und Fahrtkosten), **Buchungsliste** (eine Zeile je Position; Filter per Adresse `von`, `bis`, `art[]`, `status[]`), **Jahresübersicht** je Kostenstelle und Art.
- **Aufbewahrung** (`forms:prune`, täglich, auch als Systemaufgabe „Anträge aufräumen“): Kontodaten 30 Tage nach Auszahlung/Ablehnung/Abschluss löschen; Anträge samt Belegen 10 Jahre nach Ende des Jahres der Auszahlung; ruhende Entwürfe nach 90 Tagen.
- **Kontolöschung:** Anträge bleiben (Aufbewahrungspflicht), Name, E-Mail und Kontodaten werden entfernt.
- `FORMS_ENABLED_TYPES` (Standard `expense,travel,purchase`) schaltet Arten frei.

Noch offen: Aufgabenübersicht („X Anträge warten“), Abschnitt in der Datenschutzerklärung, Seminarseite mit Summen der Anträge, Weiterführung Bestellung → Erstattung.

## Bestellung → Erstattung
Bei einer eigenen, freigegebenen Bestellung (Status freigegeben, bestellt, geliefert oder abgeschlossen) gibt es auf der Antragsseite den Knopf **Aus eigener Tasche bezahlt: Erstattung beantragen**. Er legt einen Erstattungsantrag als Entwurf an: Positionen und Beträge, Zweck („Erstattung zur Bestellung M-…“), Seminar und Kostenstelle werden übernommen. Belege und Bankverbindung ergänzt die Person im Entwurf selbst. Pro Bestellung gibt es höchstens einen Erstattungsantrag; beide Anträge verweisen mit der Nummer aufeinander. Der Verlauf der Bestellung zeigt „Erstattung angelegt“.

## Kostenstelle am Seminar
Die Kostenstelle eines Seminars ist eine Auswahl der aktiven Kostenstellen. Anträge zu diesem Seminar übernehmen sie, wenn niemand eine andere wählt.
