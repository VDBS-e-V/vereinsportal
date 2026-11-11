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

**Wortlaut:** Version 1 (bei neuen Installationen) bzw. Version 2 (bei bestehenden, per Migration) entspricht der Vertragsvorlage „Dienstleistungsvertrag“ vom 08.07.2024; Kopf, Unterschriften und Fußzeile mit Vorsitz und Anschrift kommen aus den Vereinsdaten. Änderungen am Text als neue Version einspielen und rechtlich prüfen lassen (Scheinselbstständigkeit).

## Vertragsdaten der Teamenden
Teamende › **Vertragsdaten**: Steuernummer, Umsatzsteuer-Art, Bankverbindung. Freiwillig, verschlüsselt, jederzeit löschbar. Die IBAN wird geprüft. Am Vertrag kann die Koordination die Daten für künftige Verträge ins Teamkonto übernehmen. Ohne Teamkonto trägt die Koordination die Daten am Vertrag ein oder druckt Linien.

## Datenschutz
Der gesamte Vertragsinhalt (auch Steuernummer und IBAN) liegt verschlüsselt in der Datenbank. Er steht nie in Listen und nie im Protokoll; das Protokoll nennt nur Seminarnummer und Zahl. Aufbewahrung 10 Jahre nach dem letzten Termin; `contracts:prune` (täglich, auch als Aufgabe „Verträge aufräumen“) löscht danach.

## Erinnerung und Seminarleitung
- `contracts:remind` (täglich, auch als Aufgabe „An fehlende Vertragsscans erinnern“) schickt der Koordination drei Tage nach dem letzten Termin eine E-Mail, wenn Verträge noch keinen unterschriebenen Scan haben. Einmal je Vertrag; die Mail nennt nur Seminar und Zahl.
- **Seminarleitung:** Im Team des Seminars gibt es für bestätigte Personen den Knopf „Als Leitung“. Für die Leitung rechnet das Portal den Satz aus dem Kostenvoranschlag zum Honorar dazu (ohne Vermerk, weil es der Kostenvoranschlag so vorsieht). Gilt für Verträge, die danach erzeugt werden; bestehende Verträge ändern Sie von Hand. Ohne Markierung steht der Satz nur als Hinweis am Vertrag.

## Anleitung in einfacher Sprache
1. Öffnen Sie das Seminar. Suchen Sie den Abschnitt „Verträge“.
2. Klicken Sie auf „Alle Verträge erzeugen“. Für jede Person im Team entsteht ein Vertrag.
3. Klicken Sie bei einer Person auf „Bearbeiten“. Prüfen Sie Name, Anschrift und Geld. Leere Felder dürfen leer bleiben. Die Person schreibt sie dann von Hand.
4. Klicken Sie auf „Alle zum Drucken“ und drucken Sie die Verträge aus.
5. Die Verträge werden an den Seminartagen unterschrieben.
6. Scannen Sie jeden unterschriebenen Vertrag. Laden Sie den Scan bei der Person mit „Scan ablegen“ hoch.
7. Wenn alle Scans da sind, ist im Abschluss des Seminars der Punkt „Verträge sind unterschrieben“ erledigt.

