# 55 – Übersichten der Bereiche Mitglieder, Vorstand und Bildungsreferenten

Ergänzt `docs/43_BEREICHE.md`. Jeder Bereich hat jetzt eine Startseite („Übersicht“). Die Chips auf der Startseite führen Angemeldete zur ersten Seite ihres Bereichs.

| Bereich | Adresse | Berechtigung | Inhalt |
|---|---|---|---|
| Mitglieder | `/mitglieder` | `member.area` (Rolle Vereinsmitglied) | eigene Mitgliedschaft (Status, seit, letzter Tag), aktuelle **Satzung** und **Willkommensbrief** als PDF, Veranstaltungen mit offener Anmeldung, Kontakt zum Vorstand für Fragen und Austritt |
| Vorstand | `/vorstand` | `memberships.view` (Rolle Vorstandsmitglied) | Kennzahlen: aktive Mitglieder, Anträge ohne Entscheidung, geplante Austritte, neue Zugangsanträge, Teamende und offene Einarbeitungen, Seminare in Planung bzw. terminiert/bestätigt, Veranstaltungen mit offener Anmeldung. Nur Zahlen, keine Personendaten |
| Bildungsreferenten | `/bildung` | `education.view` (Rolle Bildungsreferenten) | Seminare nach Stand, nächste Seminartermine mit Schule, Veranstaltungen mit offener Anmeldung |

**Dokumente für Mitglieder:** Die Downloads (`/mitglieder/dokumente/{id}`) liefern nur die jeweils aktuelle Fassung von Satzung und Willkommensbrief. Ältere Fassungen und die Erklärungen der Teamenden bekommen sie nicht.

**Bildungsreferenten** erhalten außerdem **Lesezugriff** (`seminars.view`, `events.view`) und sehen dafür im Bereich Koordination die Seiten Seminare, Kostenvoranschläge, Texte, Preise und Veranstaltungen. Ändern können sie nichts (`seminars.manage`, `events.manage` fehlen), Schulen und Teamende sehen sie nicht. Alter und Geschlecht der Teamenden bei der Teamauswahl bleiben bei `seminars.manage`.

## Jahresbericht für den Vorstand (`/vorstand/berichte`, Berechtigung `reports.view`)

Der Vorstand sieht je Kalenderjahr Kennzahlen (Jahr wählbar) und kann sie als CSV herunterladen (`/vorstand/berichte/export?jahr=…`). Nur Zahlen, keine Personendaten. Berechnet in `App\Support\YearReport`.

| Bereich | Kennzahlen |
|---|---|
| Seminare | durchgeführte Seminare (Status „Durchgeführt“ oder „Abgeschlossen“ mit Terminen im Jahr), Seminartage, beteiligte Schulen, Aufteilung nach Leistung |
| Kostenvoranschläge | erstellte Kostenvoranschläge im Jahr, Summe der Endsummen |
| Teamende | Teamende im Einsatz (im Team bei durchgeführten Seminaren des Jahres) |
| Veranstaltungen | Großveranstaltungen mit Terminen im Jahr, bestätigte Anmeldungen (ohne Warteliste), beteiligte Schulen, Teilnehmende mit Anwesenheit, Anwesenheiten je Tag |
| Mitglieder | Mitglieder am 31.12., neue Mitglieder im Jahr, Austritte im Jahr |
