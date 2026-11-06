# Honorarvertrag (Paket K)

Für bestätigte Teamende erzeugt das Portal den **Dienstleistungsvertrag** des Vereins zum Ausdrucken. Unterschrieben wird auf Papier an den Seminartagen; danach wird der Scan im Portal abgelegt. Das Portal versendet keine Verträge.

## Ablauf
1. Seminar öffnen, Abschnitt **Verträge** (erscheint, sobald eine Person bestätigt ist). Wer: Recht `contracts.manage` (Koordination, Geschäftsstelle, Vorstand).
2. **Verträge für die Auswahl erzeugen** oder **Alle Verträge erzeugen**. Nummer `K-JJJJ-NNNN`. Vorbelegt werden Name, Anschrift (Teamkonto oder Kontaktangaben), Leistung (Seminar, Schule, Termine, Stunden) und die Vergütung aus dem Kostenvoranschlag (Honorarzeile geteilt durch die Zahl der Teamenden).
3. **Bearbeiten:** Leistung, Vergütung (Abweichung nur mit Vermerk), Umsatzsteuer, Bankverbindung, Ort und Datum. Fehlende Werte erscheinen im Ausdruck als Linie zum Ausfüllen von Hand; sind Umsatzsteuer-Art und Datum leer, bleiben die Kästchen leer.
4. **Ansehen und drucken** (eine Seite je Vertrag) oder **Alle zum Drucken** (Sammeldruck aller gültigen Verträge).
5. **Scan ablegen** (PDF, JPEG, PNG): wird als Seminar-Dokument der Art Vertrag abgelegt, der Vertrag ist „Unterschrieben“ und nicht mehr änderbar. Die Abschlussliste des Seminars zeigt „Verträge sind unterschrieben“, sobald jede bestätigte Person einen Scan hat. Der Punkt erscheint nur, wenn es Verträge gibt.
6. **Stornieren** geht nur vor der Unterschrift.

## Vertragstext und Vereinsdaten
Koordination › **Verträge**: Vereinsdaten (Name, Vorsitz, Anschrift, Ort) ändert jede Person mit `contracts.manage`, den **Vertragstext** nur der Vorstand (`contracts.template`). Jede Änderung ist eine neue Version; bestehende Verträge behalten ihre Fassung. Platzhalter: `{auftragnehmer_name}`, `{auftragnehmer_anschrift}`, `{steuernummer}`, `{leistung}`, `{verguetung}`, `{umsatzsteuer_auswahl}`, `{bank}`, `{bic}`, `{iban}`, `{ort}`, `{datum}`, `{verein_name}`, `{verein_vorsitz}`, `{verein_anschrift}`.

**Wichtig:** Die Vorlage `Vertragsvorlage_-1.pdf` lag dem Projekt nicht bei. Version 1 ist ein **Entwurf nach der Klauselliste** (Selbstständigkeit, Steuern und Sozialabgaben, Fahrtkosten nach Bundesreisekostengesetz, keine Ausfallvergütung). Der Vorstand spielt den Wortlaut der Vorlage als Version 2 ein; er sollte rechtlich geprüft werden (Scheinselbstständigkeit, Reisekostensatz, Q12).

## Vertragsdaten der Teamenden
Teamende › **Vertragsdaten**: Steuernummer, Umsatzsteuer-Art, Bankverbindung. Freiwillig, verschlüsselt, jederzeit löschbar. Die IBAN wird geprüft. Am Vertrag kann die Koordination die Daten für künftige Verträge ins Teamkonto übernehmen. Ohne Teamkonto trägt die Koordination die Daten am Vertrag ein oder druckt Linien.

## Datenschutz
Der gesamte Vertragsinhalt (auch Steuernummer und IBAN) liegt verschlüsselt in der Datenbank. Er steht nie in Listen und nie im Protokoll; das Protokoll nennt nur Seminarnummer und Zahl. Aufbewahrung 10 Jahre nach dem letzten Termin; `contracts:prune` (täglich, auch als Aufgabe „Verträge aufräumen“) löscht danach.

## Noch nicht umgesetzt
PDF: Die Druckansicht bietet „Als PDF herunterladen“, sobald das Paket dompdf installiert ist (`composer require dompdf/dompdf --ignore-platform-req=ext-sodium`, danach Cache leeren); sonst „Als PDF speichern“ im Druckdialog. Erinnerung an fehlende Scans in der Aufgabenübersicht. Zuschlag für die Seminarleitung (in der Vergütung von Hand anpassen). Anleitung in einfacher Sprache.
