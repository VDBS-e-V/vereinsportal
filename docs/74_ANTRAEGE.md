# Anträge (Paket G, Teil 1: Ablauf und Kostenrückerstattung)

## Wer was darf
| Recht | Rollen | Bedeutung |
|---|---|---|
| `forms.submit` | Teamende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand, Mitarbeitende, Geschäftsführung, Kassenführung | eigene Anträge stellen (Bereich **Anträge**) |
| `forms.view` | Geschäftsstelle, Vorstand, Geschäftsführung, Kassenführung | Eingang und Prüfseite sehen (Geschäftsstelle › Anträge) |
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
