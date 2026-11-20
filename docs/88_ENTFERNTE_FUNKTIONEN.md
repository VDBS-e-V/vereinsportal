# Entfernte Funktionen: Seminare, Kostenvoranschläge, Verträge, Veranstaltungen

**Hinweis:** Der Seminarablauf wurde danach neu aufgebaut, siehe `docs/89_SEMINARABLAUF.md`. Diese Liste beschreibt, was zwischenzeitlich entfernt war.

Aus dem Portal entfernt (Branch `claude/cool-ramanujan-fgmbcu`, Stand vor der Entfernung: Commit `f8866bb`, dort per `git show f8866bb:<Pfad>` lesbar):

- **Seminare** samt Terminen, Verlauf, Status, Seminarliste, Statistik, Seminarmappe (ZIP), Druckplaner, Nextcloud-Ordner, Seminarleitung, Abschluss.
- **Kostenvoranschläge** (Preislisten, Texte, Berechnung) und **Verträge** (Honorarverträge der Teamenden, Vertrag mit der Schule, Vertragsdaten der Teamenden).
- **Bewerbungen der Teamenden** (Interesse, Bewerbungslink, Teamprofil, Warteliste) und der Seiten „Seminare“, „Profil“, „Vertragsdaten“ im Bereich Teamende.
- **Feedback (Rückmeldungen)**: Feedbackformulare, Links, Auswertung. Das Modul hing vollständig an Seminaren.
- **Briefvorlagen** (Word-Vorlagen mit Seminar-Platzhaltern). Das Modul erzeugte nur Schreiben zu Seminaren.
- **Schulanfragen**: Seminar anfragen, KVA bestätigen, Rückmeldung an die Koordination. Der Schulbereich zeigt nur noch eine Startseite.
- **Mitteilungen an Teamende zu Seminaren** und die Seminar- und Teamprofil-Filter der Verteiler.
- Verknüpfung von Anträgen (Kostenerstattung) und Rundschreiben mit Seminaren, Seminarkennzahlen im Jahresbericht, in den Übersichten der Bereiche Vorstand und Bildungsreferenten und in der Aufgabenübersicht.

Die Migration `2027_01_18_000001_remove_seminars_quotes_and_contracts` löscht die zugehörigen Tabellen und die Spalten `seminar_id` in Anträgen und Rundschreiben. **Das lässt sich nur über eine Datenbanksicherung rückgängig machen.** Ältere Migrationen bleiben unverändert.

- **Veranstaltungen** (Großveranstaltungen): öffentliche Anmeldung mit Bestätigung und Ticketcode, Warteliste, Anwesenheit und Teilnahmebestätigung, Nachrichten an Angemeldete, Anmeldelisten der Schulen, „Meine Anmeldungen“, Erinnerungen und Aufbewahrungsfristen sowie die Kennzahlen im Jahresbericht und in den Übersichten.

Die Migration `2027_01_19_000001_remove_events` löscht die Tabellen der Veranstaltungen. **Auch das lässt sich nur über eine Datenbanksicherung rückgängig machen.**

Geblieben: gemeinsame Regeln für Dateiuploads (`App\Support\Files\PrivateUploads`, Aufgaben, Tickets, Vorstandssitzungen), Beträge (`App\Support\Money`), das Koordinationsgebiet in den Stammdaten der Schule, Anträge, Rundschreiben, der persönliche Zugang für Kontakte ohne Konto (ohne Bewerbungen).
