# Offene Fragen

Stand: nach der zweiten Antwortrunde (siehe `02_ENTSCHEIDUNGEN.md`). Die Fragen R1 bis R14 sind beantwortet (Tabelle weiter unten als Archiv). **Offen sind nur noch Q1 bis Q7.** Wo Sie nicht antworten, gilt die Annahme.

## Offen
| Nr. | Thema | Frage | Annahme |
|---|---|---|---|
| Q1 | Mailsystem (C0) | In welchem Mailsystem liegt die Liste (Strato-Mail, Mailman, Brevo, Mailchimp, …)? Lässt sie sich als CSV mit einer Spalte E-Mail exportieren? Wie viele Adressen? | CSV-Export, bis 3 000 Adressen |
| Q2 | Zugang (C0) | Da die Liste nur Adressen enthält: Darf die Bewerbungsseite beim ersten Aufruf Vorname und Nachname erfragen und der Adresse zuordnen? | ja, einmalig, danach vorbelegt |
| Q3 | Rollen (I) | Welche **besonderen Rechte** hat die Geschäftsführung außer der Freigabe ab 50 €? Zum Beispiel Rundschreiben freigeben, Mitgliedschaften sehen, Protokoll lesen, Kostenstellen pflegen. | Freigabe ab 50 €, Kostenstellen pflegen, Nextcloud, Zwei-Faktor Pflicht; sonst nichts |
| Q4 | Rollen (I) | Welche Rechte und welchen Bereich bekommen **Mitarbeitende**? Brauchen sie einen eigenen Bereich „Mitarbeitende“ (Start, Anträge, Nextcloud-Link), und soll der **Einsatzort** (z. B. „Schulbibliothek X“) erfasst werden? | Eigener kleiner Bereich mit Anträgen und Nextcloud-Link; Einsatzort als Freitext in der Rollenvergabe |
| Q5 | Übergang (G) | Solange es die Stelle in der Geschäftsstelle nicht gibt, soll der **Kassierer im Vorstand** die Prüfung der Anträge und die Ausnahmen bei der Belegfrist übernehmen? Dann bekommt er eine Zusatzrolle „Kassenführung“ mit `forms.manage`. Wer prüft die Anträge der Kassenführung selbst? | Zusatzrolle „Kassenführung“; eigene Anträge der Kassenführung prüft eine andere Person mit `forms.approve` |
| Q6 | Filter (H) | Weitere Filterwünsche außer PLZ, Alter, Mitgliedsstatus? (Das „…“ in Ihrer Antwort.) | Bundesland, Schulart, Seminar, Einarbeitung, Kompetenzen, Zugang gesendet/benutzt |
| Q7 | Strato (A) | Welcher externe Cron-Dienst? Und sind `zip`, `gd`, `intl` im Tarif vorhanden? (Nicht beantwortet.) | cron-job.org, alle vorhanden |

## Archiv: Fragen R1 bis R14 (beantwortet)

| Nr. | Thema | Frage | Annahme |
|---|---|---|---|
| R1 | Mailingliste (C0) | Wie sind die Adressen der Liste entstanden? Liegt eine Einwilligung zur E-Mail-Kommunikation vor, und gibt es Abgemeldete im Altsystem, die nicht angeschrieben werden dürfen? | Einwilligung liegt vor, Abgemeldete werden vorher entfernt oder mit importiert |
| R2 | Mailingliste (C0) | Welche Spalten hat die Liste (E-Mail, Vorname, Nachname, Telefon, Kompetenzen, Bundesland, Notiz)? Wie viele Personen? Nur Teamende oder auch Mitglieder, Schulen, Lehrkräfte? | CSV mit Vorname, Nachname, E-Mail; nur Teamende; bis 3 000 Personen |
| R2a | Zugang (C0) | Wie soll der Zugang an die Person gehen: nur per E-Mail mit Link und Code? Wie lange gilt er (unbegrenzt, 12 Monate)? Darf die Person den Zugang selbst neu anfordern? | E-Mail mit Link und Code, gültig bis zum Widerruf, Neuanforderung per Adresse erlaubt |
| R2b | Zugang (C0) | Wofür darf der Zugang genutzt werden: Bewerben, Anträge stellen, eigene Kontaktdaten ändern, später Konto aktivieren. Gibt es weitere Zwecke (Feedback als Teamende, Dokumente, Einarbeitung)? | genau diese vier |
| R3 | Rollen | „Mitarbeitende“ und „Geschäftsführung“ gibt es im Portal nicht. Neue Rollen anlegen? Was unterscheidet Mitarbeitende von der Geschäftsstelle, und wer ist die Geschäftsführung? | zwei neue Rollen: **Mitarbeitende** (Antrag stellen, Nextcloud) und **Geschäftsführung** (Freigabe ab 50 €, Nextcloud, Zwei-Faktor Pflicht) |
| R4 | SSO | Adresse und Version von Nextcloud und Moodle? In Moodle: Sollen Rollen Kursen zugeordnet werden oder nur die Anmeldung? | nur Anmeldung, keine Kurszuordnung |
| R5 | Belegfrist (G) | Ist die Regel „Belegdatum im Kalenderjahr, einreichbar bis Ende Februar des Folgejahres“ eine **harte Sperre** oder eine Warnung? Darf die Geschäftsstelle Ausnahmen freischalten? | harte Sperre, Ausnahme durch Geschäftsstelle mit Begründung |
| R6 | Freigabe (G) | Gilt die 50-€-Grenze für die **Gesamtsumme** des Antrags (nicht je Position)? Gilt sie auch für Materialbestellungen? Dürfen Geschäftsführung **und** Vorstand freigeben, reicht eine Person? | Gesamtsumme, alle drei Formulare, eine Person reicht |
| R7 | Feedback (E) | Dürfen Vorstand und Geschäftsstelle das **anonymisierte Archiv** (Durchschnitte und bereinigte Freitexte) lesen, sobald die Koordination es freigegeben hat? | ja, nur das Archiv |
| R8 | Feedback (E) | Ablauf des Anonymisierens: Die Koordination liest, löscht oder ändert Freitexte und klickt „anonymisiert“. Danach werden die Rohantworten durch das bereinigte Archiv ersetzt. In Ordnung? Frist, bis zu der anonymisiert sein muss? | ja, 24 Monate |
| R9 | Verteiler (H) | Welche Filter sind am wichtigsten (Bundesland, Schulart, Kompetenzen, Einarbeitung, Seminar, Mitglied seit, Zugang gesendet/benutzt, PLZ-Bereich)? | alle genannten außer PLZ-Bereich |
| R10 | Export (G5) | Welche CSV-Spalten braucht die Buchhaltung (Nummer, Name, Betrag, Kostenstelle, Seminar, Datum, Art, Konto)? Trennzeichen und Zahlenformat (Semikolon, Komma)? | Semikolon, Dezimalkomma, UTF-8 mit BOM |
| R11 | Texte | Bitte die Satzungstexte (Einladungsfrist, Antragsfrist) und die Datenschutztexte als Datei oder Text liefern, damit sie exakt übernommen werden. | Platzhalter bis zur Lieferung |
| R12 | Strato | Ist PHP 8.4 im Strato-Tarif wählbar? Welcher externe Cron-Dienst (z. B. cron-job.org)? Hat der Tarif Erweiterungen `zip`, `gd`, `intl`? | PHP 8.4 wählbar, cron-job.org, alle Erweiterungen vorhanden |
| R13 | Kostenstellen | Gibt es feste Kostenstellen, die als Auswahl hinterlegt werden sollen? | Freitext, später Auswahl |
| R14 | Datenschutz | Wer ist Datenschutzbeauftragte und prüft die Anonymität des Schülerfeedbacks, den Umgang mit der Mailingliste und die Auftragsverarbeitung (Strato, Nextcloud, Moodle)? | Vorstand benennt eine Person |

## Rechtlich zu klären (nicht im Code lösbar)
- Auftragsverarbeitung mit Strato und den Betreibern von Nextcloud und Moodle.
- Aufbewahrungsfristen mit der Steuerberatung bestätigen.
- Neue Abschnitte in der Datenschutzerklärung (Teambewerbung und Zugang, Feedback, Kostenerstattung, SSO).
- Einladung zur Mitgliederversammlung per E-Mail: Was erlaubt die Satzung? (Aus `docs/65`.)
