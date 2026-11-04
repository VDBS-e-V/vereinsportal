# Paket E: Digitale Feedbackformulare (SuS, Lehrkräfte, Teamende)

Aufwand: **11 ET**. Ziel: Rückmeldungen zu jedem Seminar werden digital, **ohne Konto und ohne personenbezogene Daten der Schülerinnen und Schüler** erhoben, ausgewertet und im Seminar gesichert.

## 1 Ausgangslage
- Es gibt nur den Freitext „Rückmeldung“ der Schule (`SchoolRequests::feedback`, ein Textfeld ohne Auswertung).
- Vorhanden und nutzbar: Formularschutz (`bot guard`), Drosselung, Mehrfachauswahl-Komponente, Radiogruppen, Eingabekomponenten, QR-fähige Druckansichten (Muster Versandbescheinigung), Mailing mit Platzhaltern.
- SuS sind oft minderjährig und haben keine Konten.

## 2 Entscheidungen

| Frage | Entscheidung |
|---|---|
| Wer füllt aus? | **SuS** (im Unterricht, per QR-Code/Link, Handy oder Schulgerät), **Lehrkräfte** (Link per Mail), **Teamende** (Link per Mail oder Konto später). |
| Anonym? | SuS und Lehrkräfte: **vollständig anonym** (keine Namen, keine IP, keine Zeitstempel unter einer Minute, kein Cookie als Kennung). Teamende: wahlweise mit Namen (Auswahl im Formular). |
| Ein Link für viele | Ein Link je Seminar und Zielgruppe, viele Antworten. Begrenzung durch Gültigkeitszeitraum und Höchstzahl (Standard: Teilnehmendenzahl × 1,5). |
| Mehrfachabgabe | Nicht technisch verhindert (sonst bräuchte man Kennungen). Gegenmaßnahmen: Drosselung je Link und Schutzfeld, Auswertung zeigt „Antworten insgesamt“ gegen erwartete Zahl. |
| Auswertung | Erst ab **mindestens 5 Antworten** je Zielgruppe (einstellbar), sonst „zu wenige Antworten“. Freitexte erst ab 5 Antworten sichtbar, damit einzelne Personen nicht erkennbar werden. |
| Formulare | Fest mitgelieferte Standardformulare (siehe 4), **bearbeitbar** durch die Koordination (Versionen). |
| Sprache | Einfache Sprache für SuS (Klasse 5 bis 13), deutsch. Mehrsprachigkeit nicht in dieser Runde. |

## 3 Datenmodell

**`feedback_forms`**
| Spalte | Hinweis |
|---|---|
| id, key | `students`, `teachers`, `team` (Standard) und frei vergebbar |
| audience | `students|teachers|team` |
| title, intro | Kopf und Einleitung (Datenschutzhinweis) |
| version | steigt bei jeder Änderung |
| questions | json: Liste `{id, type, text, hint, required, options, scale_labels}` |
| active | bool |
| updated_by_user_id | |

**`feedback_form_versions`** (Kopie je Version, damit alte Antworten ihre Fragen behalten): `form_id`, `version`, `questions`, `intro`, `created_at`.

**`feedback_links`**
| Spalte | Hinweis |
|---|---|
| seminar_id | FK |
| form_id, form_version | |
| audience | |
| token_hash | SHA-256, Index |
| opens_at, closes_at | |
| max_responses | uint null |
| closed_at | manuell geschlossen |
| label | z. B. „Klasse 8b“ (Klassenlink, optional, **kein Personenbezug**) |

**`feedback_responses`**
| Spalte | Hinweis |
|---|---|
| link_id | FK |
| submitted_on | **nur Datum** (Tag) |
| answers | json: `{frage_id: wert}` |
| respondent_name | nur Teamende, optional |

Keine IP-Adresse, kein User-Agent, keine Sitzung in den Daten. Das Standardprotokoll des Webservers wird vom Hoster geführt; Hinweis im Datenschutztext.

## 4 Fragenkataloge (Entwurf zur Freigabe)

Fragetypen: **Skala 1–5** (Smileys und Text: 1 trifft gar nicht zu … 5 trifft voll zu), **Ja/Nein**, **Auswahl**, **Mehrfachauswahl**, **Freitext (max. 500 Zeichen)**. Pflichtfragen mit * .

### 4.1 SuS (Einleitung: „Danke, dass du mitmachst. Du gibst keinen Namen an. Niemand kann sehen, wer was geschrieben hat. Du brauchst etwa 3 Minuten.“)
1. *Wie hat dir das Seminar insgesamt gefallen?* (Skala, Smileys)
2. *Ich habe etwas Neues gelernt.* (Skala)
3. *Ich konnte meine Meinung sagen und wurde gehört.* (Skala)
4. *Die Teamerinnen und Teamer haben alles gut erklärt.* (Skala)
5. *Die Aufgaben und Übungen waren verständlich.* (Skala)
6. *Das Tempo war …* (Auswahl: zu langsam / genau richtig / zu schnell)
7. *Was hat dir am besten gefallen?* (Freitext)
8. *Was sollten wir beim nächsten Mal anders machen?* (Freitext)
9. *Würdest du anderen Klassen das Seminar empfehlen?* (Ja/Nein)
10. *Welche Klassenstufe besuchst du?* (Auswahl: 5–6, 7–8, 9–10, 11–13; hilft bei der Auswertung)

### 4.2 Lehrkräfte (Einleitung: „Ihre Antworten sind anonym und helfen uns, die Seminare zu verbessern. Dauer etwa 5 Minuten.“)
1. *Wie zufrieden sind Sie insgesamt mit dem Seminar?* (Skala)
2. *Die Ziele des Seminars wurden erreicht.* (Skala)
3. *Der Ablauf war gut abgestimmt und pünktlich.* (Skala)
4. *Die Kommunikation vor dem Seminar (Anfrage, Terminabsprache, Kostenvoranschlag) war klar.* (Skala)
5. *Die Teamenden waren fachlich und pädagogisch überzeugend.* (Skala)
6. *Die Schülerinnen und Schüler haben sich aktiv beteiligt.* (Skala)
7. *Der Aufwand für die Schule war angemessen.* (Skala)
8. *Wie ist das Verhältnis von Preis und Leistung?* (Auswahl: zu hoch / angemessen / günstig)
9. *Würden Sie ein weiteres Seminar buchen?* (Ja/Nein/Vielleicht)
10. *Welche Themen wünschen Sie sich in Zukunft?* (Mehrfachauswahl: Klassenrat, SV-Arbeit, Schülervertretungswahl, Konflikte, Beteiligung, Medien, Sonstiges)
11. *Was hat gut funktioniert?* (Freitext)
12. *Was sollten wir ändern?* (Freitext)

### 4.3 Teamende (Einleitung: „Danke für deinen Einsatz. Mit deinen Antworten verbessern wir Vorbereitung und Begleitung. Du kannst anonym bleiben.“)
1. *Wie gut war die Vorbereitung (Infos, Materialien, Absprachen)?* (Skala)
2. *Die Rollen im Team waren klar verteilt.* (Skala)
3. *Die Zusammenarbeit im Team war gut.* (Skala)
4. *Die Gruppe war interessiert und offen.* (Skala)
5. *Die Rahmenbedingungen an der Schule waren gut (Raum, Technik, Zeit).* (Skala)
6. *Die Begleitung durch die Koordination war hilfreich.* (Skala)
7. *Die Zeit hat gereicht.* (Auswahl: zu knapp / passend / zu viel)
8. *Gab es Vorfälle, über die wir sprechen sollten?* (Ja/Nein; bei Ja Freitext – Hinweis „Bei Vorfällen melde dich bitte zusätzlich direkt bei der Koordination“)
9. *Was hat gut funktioniert?* (Freitext)
10. *Was braucht das nächste Team von uns?* (Freitext)
11. *Möchtest du namentlich antworten?* (Ja/Nein; bei Ja Namensfeld)

Alle Kataloge werden vor dem Bau von der Koordination durchgesehen (Streichen, Umformulieren, Ergänzen). Änderungen später im Editor (E3).

## 5 Arbeitspakete

### E1 Datenmodell, Dienste und Versionen (1,5 ET)
Migrationen, Modelle, `FeedbackForms` (laden, speichern mit Versionssprung, Standardformulare säen), `FeedbackLinks` (erzeugen, schließen, prüfen: offen, Zeitraum, Höchstzahl), Seeder/Migration mit den drei Standardformularen (`FeedbackDefaults`). Validierungsdienst `FeedbackValidator` baut die Regeln aus der Formularversion (Pflicht, Wertebereiche, Länge).

### E2 Öffentliche Seite für Antworten (2,5 ET)
Routen ohne Anmeldung: `GET /rueckmeldung/{token}` und `POST /rueckmeldung/{token}`, Danke-Seite `…/danke`.
- Leichtgewichtiges, mobil optimiertes Layout (große Tippflächen, Smileys mit Text, kein JavaScript nötig, JS optional für Fortschritt).
- Formularschutz, Drosselung (`throttle:30,1` je Link über Schlüssel `token`), Prüfung gegen Zeitraum und Höchstzahl.
- Antwort speichern: nur Datum, Antworten, ggf. Name (Teamende).
- Freundliche Fehlerseiten: noch nicht offen, geschlossen, abgelaufen, voll.
- Datenschutzhinweis als sichtbarer Text.
- Barrierefreiheit: Fragen als Fieldset/Legend, Skala als Radiogruppe, Tastatur, Fehlerzusammenfassung; Smileys mit Textalternative.

### E3 Verwaltung für Koordination (2 ET)
Bereich Koordination, Seiten „Rückmeldungen“:
- **Formulare** (Liste, bearbeiten): Editor mit Fragenliste (Reihenfolge ändern per Pfeiltasten, Fragetyp, Text, Hinweis, Pflicht, Optionen, Skalenbeschriftung), Vorschau, Speichern erzeugt neue Version. Zurücksetzen auf Standard. Rechte `feedback.manage`.
- **Links je Seminar** (Seminarseite, Abschnitt Rückmeldungen): Link erzeugen je Zielgruppe (optional Beschriftung „Klasse 8b“), Zeitraum (Standard: Seminartag bis +14 Tage), Höchstzahl, Link kopieren, **QR-Code** und Druckansicht mit Anleitung für die Lehrkraft („Bitte Link/QR im Unterricht zeigen“), Schließen, Rücklaufzähler.
- Versand des Links an Lehrkräfte/Teamende: Rundschreiben-Platzhalter `{feedback_link_lehrkraft}` und `{feedback_link_team}` und Vorlage „Feedback-Bitte“ (Paket H). Der Platzhalter wird je Seminar aufgelöst (Teamende-Mitteilungen enthalten ein Seminar).
- Erinnerung: optional automatische Erinnerung an Lehrkraft/Team, wenn nach 7 Tagen weniger als die Hälfte geantwortet hat (Planerbefehl `feedback:remind`, nur Zahl im Protokoll).

### E4 Auswertung (2,5 ET)
- Seite **Auswertung je Seminar**: je Zielgruppe Antworten insgesamt, Rücklaufquote (gegen erwartete Zahl), je Skalenfrage Durchschnitt, Verteilung 1–5 als Tabelle, Ja/Nein-Anteile, Auswahlhäufigkeiten, Freitexte (erst ab Mindestanzahl) als Liste ohne Reihenfolge der Abgabe (zufällig sortiert, damit die Reihenfolge keine Person verrät).
- Seite **Gesamtauswertung**: Filter Jahr, Leistungsart, Schule; Durchschnitte pro Frage über Seminare; Entwicklung pro Jahr als Tabelle.
- Export CSV (Aggregate, Formelschutz) und Druckansicht.
- Kennzahlen gehen in den Seminar-Schnappschuss (Paket D3/D4).
- Mindestanzahl einstellbar (`portal.feedback.min_responses`, Standard 5).

### E5 Aufbewahrung und Löschung (0,5 ET)
Befehl `feedback:prune`: löscht Rohantworten 24 Monate nach Linkende (einstellbar), behält Aggregat im Schnappschuss. Löscht Links und Namen der Teamenden. Systemsteuerung (hoch), Planer täglich. Abschnitt in der Datenschutzerklärung.

### E6 Ablösung der alten Rückmeldung (0,25 ET)
Das Freitext-Feedback der Schule bleibt als „Nachricht an die Koordination“ bestehen (Umbenennung), der Link zu den Formularen wird auf der Seminarseite der Schule angezeigt, sobald ein Lehrkraft-Link existiert.

### E7 Tests (im Aufwand enthalten)
`FeedbackFormsTest`, `FeedbackPublicTest`, `FeedbackEvaluationTest`, `FeedbackPruneTest`:
1. Standardformulare sind gesät, Fragenzahl je Zielgruppe passt.
2. Antwort ohne Konto speichert Datum und Antworten, **keine IP, kein User-Agent** (Spaltenprüfung und Inhaltsprüfung).
3. Pflichtfragen, Wertebereiche, Länge; Ungültige Skala wird abgelehnt.
4. Link: noch nicht offen / geschlossen / abgelaufen / voll liefern jeweils die richtige Seite.
5. Drosselung je Link, Formularschutz.
6. Versionierung: Änderung erhöht die Version, alte Antworten bleiben gültig.
7. Auswertung: Durchschnitte und Verteilungen stimmen, unter 5 Antworten keine Freitexte, Zufallsreihenfolge (kein Rückschluss auf Abgabereihenfolge).
8. Rechte: `feedback.manage` bearbeitet Formulare, Geschäftsstelle sieht nur Kennzahlen, Teamende und Schulen nichts.
9. Löschen nach Frist.
10. Teamende-Namen nur bei Auswahl „namentlich“.
11. Erinnerung: wird nur einmal und nur bei geringem Rücklauf verschickt.

## 6 Datenschutz und rechtliche Hinweise
- Keine personenbezogenen Daten von SuS. Dennoch Datenschutzhinweis auf der Seite; Einwilligung nicht nötig, weil keine personenbezogenen Daten erhoben werden (Annahme, bitte vom Datenschutzbeauftragten bestätigen lassen).
- Freitexte können Namen enthalten: Hinweis „Bitte schreibe keine Namen“ am Textfeld; Koordination darf einzelne Freitexte als unzulässig **ausblenden** (Markierung, nicht Löschen) und löschen.
- Zugriff auf Roh-Freitexte nur `feedback.view`.

## 7 Abnahme (Teil von M3)
1. Link und QR für eine Klasse erzeugen, drei Handys antworten.
2. Auswertung zeigt „zu wenige Antworten“, nach fünf Antworten die Zahlen.
3. Lehrkraft-Link per Rundschreiben verschickt, Antwort kommt an.
4. Seminarabschluss enthält die Feedback-Kennzahlen.

## 8 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| Spam und Mehrfachabgabe | Drosselung, Schutzfeld, Höchstzahl, Auswertung mit Rücklaufquote |
| Rückschluss auf Einzelne bei kleinen Gruppen | Mindestanzahl, zufällige Sortierung, kein Datum der Abgabe unter Tag |
| Viele gleichzeitige Antworten (Klasse von 30 auf einmal) | einfaches Formular, kein JS-Zwang, Hosting-Last gering |
| Unpassende Fragen | Freigabe der Kataloge vor dem Bau, Editor |

## 9 Offene Fragen
1. Katalog-Entwürfe (4.1–4.3) durchsehen: streichen, ergänzen?
2. Mindestanzahl Antworten 5 in Ordnung?
3. Soll das Feedback der Lehrkräfte optional mit Schulname erfolgen (Vorschlag: nein, der Link gehört zum Seminar und damit zur Schule)?
4. Aufbewahrung der Rohantworten 24 Monate in Ordnung?
