# Paket K: Honorarvertrag (Dienstleistungsvertrag) für Teamende

Aufwand: **4,5 ET**. Ziel: Für bestätigte Teamende erzeugt das Portal den **Dienstleistungsvertrag** des Vereins als PDF, vorausgefüllt mit Person, Seminar, Terminen und Honorar. Grundlage ist Ihre Vertragsvorlage (`Vertragsvorlage_-1.pdf`, Stand 08.07.2024).

## 1 Analyse der Vorlage

| Stelle in der Vorlage | Inhalt | Quelle im Portal |
|---|---|---|
| Kopf | Logo, Titel „Dienstleistungsvertrag“, Verein als Auftraggeber | Vereinsdaten (Einstellung), Logo `public/images/brand` |
| Auftragnehmer:in | Name, Vorname · Straße/Hausnummer/PLZ · Steuernummer | Person: Name, Anschrift (Teamkonto oder freiwillig gespeicherte Kontaktangaben aus Paket C), **Steuernummer** (neu, Vertragsdaten) |
| Leistung (**rot, frei**) | „Hier Text einfügen“ | Text aus Seminar: Leistungsart, Titel, Schule, Termine, Stunden; bearbeitbar |
| Vergütung (**rot**) | „75,00 €“ | Honorar aus dem Kostenvoranschlag (Honorarsatz, Zuschlag Leitung), bearbeitbar |
| Umsatzsteuer | Auswahl: 19 %, 7 %, ohne | Vertragsdaten der Person (Standard: ohne) |
| Feste Klauseln | keine Weisungsbindung, Selbstständigkeit, Steuern und Sozialabgaben, Fahrtkosten nach Bundesreisekostengesetz, keine Ausfallvergütung bei Absage | feste Textbausteine, versioniert |
| Bank | Bankinstitut, BIC, IBAN | Vertragsdaten der Person (verschlüsselt) |
| Ort, Datum (**rot**) | „Berlin, den 08.07.2024“ | Erstelldatum (Ort aus Vereinsdaten) |
| Unterschriften | VDBS e. V. · Auftragnehmer:in | Unterschriftslinien (Unterschrift auf Papier, Scan wird abgelegt) |
| Fuß | Verband, Vorsitzender: Jan Ole Schmiedecke, Anschrift Fritz-Karsen-Schule, Onkel-Bräsig-Straße 76, 12359 Berlin | Vereinsdaten (Einstellung, Vorsitz änderbar) |

Rot markierte Stellen der Vorlage sind die **veränderlichen Felder**: Leistung, Vergütung, Datum.

## 2 Entscheidungen

| Frage | Entscheidung / Annahme |
|---|---|
| Wer erzeugt Verträge? | Koordination (`contracts.manage`), je Seminar für alle bestätigten Teamende auf einen Klick. Geschäftsstelle und Vorstand dürfen ebenfalls. |
| Wann? | Nach der Teambestätigung (Paket C5), vor dem Seminar. |
| Unterschrift | Auf Papier, **an den Seminartagen** (Antwort Q14). Das Portal erzeugt die Verträge nur zum **Ausdrucken**, es versendet sie nicht. Nach dem Seminartag wird der **Scan als Dokument abgelegt** (Seminar-Dokumente, Paket D, Art `contract`). Eine digitale Signatur ist nicht Teil dieses Pakets. |
| Fehlende Daten | Fehlt ein Wert (z. B. Steuernummer), druckt das Portal eine **leere Zeile zum Ausfüllen von Hand**. Der Vertrag lässt sich also auch ohne Vertragsdaten erzeugen. |
| Vergütung | **Übernahme aus dem Kostenvoranschlag** (Antwort Q11): Honorar nach Stunden je Tag, Zuschlag für die Leitung. Vor dem Erzeugen änderbar (mit Vermerk). |
| Umsatzsteuer | **Teamende wählen** (Antwort Q13) in ihren Vertragsdaten (19 %, 7 %, ohne). Ohne Angabe druckt der Vertrag die drei Kästchen unausgefüllt, die Person kreuzt am Seminartag an. |
| Vertragstext | Wortgleich aus der Vorlage; als **versionierter Text** im Portal (Datenbank), änderbar nur durch den Vorstand, jede Version bleibt erhalten. Platzhalter, keine freie Textbearbeitung im Einzelvertrag außer der Leistungsbeschreibung. |
| Aufbewahrung | Wie Seminarunterlagen: 10 Jahre (Abrechnungsunterlage). |

## 3 Datenmodell

**`contract_templates`**: `id`, `key` (`service_agreement`), `version`, `title`, `body` (Text mit Platzhaltern), `active`, `created_by_user_id`, `created_at`.

**`contractor_profiles`** (Vertragsdaten einer Person; nur über das **Teamkonto**, später):
`id`, `user_id` (unique), `tax_number_encrypted`, `vat_mode` (`none|7|19`), `bank_name`, `bic`, `iban_encrypted`, `account_holder`, `updated_at`. Ohne Konto: Eingabe durch die Koordination am Vertrag (siehe unten, wird nicht dauerhaft gespeichert).

**`seminar_contracts`**
| Spalte | Hinweis |
|---|---|
| id, number | `K-2027-0001` (Nummernkreis aus Paket B) |
| seminar_id | FK |
| applicant_ref | Verweis auf Teamkonto (`user_id`) oder Kontakt (`contact_id`) |
| template_id, template_version | |
| data | json: Name, Anschrift, Steuernummer (verschlüsselt), USt-Art, Bank, BIC, IBAN (verschlüsselt), Leistungstext, Betrag in Cent, Ort, Datum |
| status | `draft`, `created`, `signed`, `cancelled` |
| pdf_path | erzeugte Datei (privat) |
| signed_document_id | FK `seminar_documents` (Scan) |
| created_by_user_id, created_at, signed_at | |

## 4 Arbeitspakete

### K1 Vorlage und Vereinsdaten (0,75 ET)
- Vertragstext der Vorlage als `contract_templates`-Eintrag (Version 1) mit Platzhaltern: `{auftragnehmer_name}`, `{auftragnehmer_anschrift}`, `{steuernummer}`, `{leistung}`, `{verguetung}`, `{umsatzsteuer_auswahl}`, `{bank}`, `{bic}`, `{iban}`, `{ort}`, `{datum}`, `{verein_name}`, `{verein_vorsitz}`, `{verein_anschrift}`.
- **Vereinsdaten** als Einstellungen (Seite Geschäftsstelle › Einstellungen): Name, Vorsitz, Anschrift, Ort für den Vertrag, Logo. Der Vorstand ändert den Vorsitz hier, nicht im Code.
- Verwaltung des Vertragstexts: Ansicht, neue Version anlegen (Vorstand), Vorschau mit Beispieldaten, alte Versionen lesen.

### K2 Vertragsdaten der Person (1 ET)
- **Mit Teamkonto:** Seite „Meine Vertragsdaten“ (Konto-Bereich): Steuernummer, Umsatzsteuer-Art, Bankinstitut, BIC, IBAN, Kontoinhaber. Prüfung der IBAN (mod 97) und der Steuernummer (Länge/Format, keine inhaltliche Prüfung), **Verschlüsselung** (`encrypted`-Cast), Hinweis auf Zweck und Löschung. „Für die Zukunft speichern“ als Auswahl, ohne Speichern gelten die Angaben nur für den einen Vertrag.
- **Ohne Teamkonto (Übergang):** Der Zugang aus Paket C gilt nur für Bewerbungen (Antwort R2b). Die Koordination trägt Vertragsdaten, die sie per Telefon oder E-Mail bekommt, **direkt am Vertrag** ein, oder der Vertrag wird mit leeren Zeilen gedruckt und von Hand ergänzt.
- Anschrift: aus Konto/Person oder aus den freiwillig gespeicherten Kontaktangaben (Paket C), sonst leer.
- Datenschutz: Zugriff nur für `contracts.manage` am einzelnen Vertrag, nie in Listen, nie im Protokoll, Löschfrist wie Vertrag.

### K3 PDF-Erzeugung (1,25 ET)
- Bibliothek `dompdf/dompdf` (reines PHP, Strato-tauglich; Test in A3 „PDF-Erzeugung“). Layout nach der Vorlage: Logo oben rechts, Titel, Blöcke, Auswahlkästchen für die Umsatzsteuer (☒/☐ als Zeichen oder SVG), Unterschriftslinien, Fußzeile mit Vereinsdaten. Dekorative Flächen (grüne Formen) als einfache Hintergrundgrafik oder weggelassen; Schrift eingebettet (Poppins oder DejaVu).
- **Fallback:** Druckansicht (HTML) mit identischem Inhalt, falls PDF auf dem Hosting nicht läuft.
- Seitenumbruch bei langer Leistungsbeschreibung, Zeilen für fehlende Werte.
- Dateiname `Dienstleistungsvertrag_<Nachname>_<Seminarnummer>.pdf`.

### K4 Ablauf im Seminar (1 ET)
- Seminarseite, Abschnitt **Verträge** (sichtbar, sobald mindestens eine Person bestätigt ist): Tabelle je bestätigter Person mit Status, Vergütung, Datum, Knöpfen **Vertrag erzeugen**, **Ansehen/Herunterladen**, **Als versendet markieren**, **Unterschriebenen Scan hochladen** (PDF/JPEG/PNG, wird als Seminar-Dokument Art `contract` abgelegt), **Stornieren**.
- Sammelknopf **Alle Verträge erzeugen**; Sammel-PDF (ein Dokument, eine Seite je Vertrag) zum Drucken.
- **Vorschau und Bearbeitung vor dem Erzeugen:** Leistungstext (vorbelegt aus Seminar: „Durchführung des Seminars <Leistung> <Titel> an der <Schule> am <Termine>, <Stunden> Stunden“), Vergütung (aus dem Kostenvoranschlag, änderbar mit Vermerk), USt-Art, Ort und Datum.
- **Kein Versand** (Antwort Q14): Die Verträge werden nur gedruckt und an den Seminartagen unterschrieben. Der Knopf „Als versendet markieren“ entfällt; Status: `created` → `signed`. Die Aufgabenübersicht erinnert nach dem letzten Seminartag an fehlende Scans.
- Abschluss-Checkliste (Paket D3): „Verträge unterschrieben“ wird automatisch erkannt, wenn jeder bestätigten Person ein unterschriebener Scan zugeordnet ist.
- Protokoll: „Vertrag erzeugt/unterschrieben“ mit Seminarnummer und Zahl, keine Namen, keine Beträge.

### K5 Tests und Doku (0,5 ET)
`ContractTest`:
1. Vertrag enthält Name, Seminar, Termine, Vergütung, Datum; fehlende Werte erscheinen als Linie.
2. Vergütung kommt aus dem Kostenvoranschlag und lässt sich ändern.
3. Umsatzsteuer-Auswahl wird richtig markiert.
4. IBAN und Steuernummer sind in der Datenbank verschlüsselt, tauchen nicht im Protokoll und nicht in Listen auf.
5. Nur `contracts.manage` erzeugt, liest, lädt herunter.
6. Versionierung: neuer Vertragstext erzeugt Version 2, alte Verträge behalten Version 1.
7. Unterschriebener Scan wird als Seminar-Dokument abgelegt und der Checkliste zugeordnet.
8. Sammel-PDF enthält so viele Seiten wie bestätigte Personen.
9. Aufräumen nach Frist (zusammen mit den Seminardokumenten).
Doku `docs/70_HONORARVERTRAG.md` und Anleitung in einfacher Sprache.

## 5 Abnahme
1. Seminar mit zwei bestätigten Teamenden: „Alle Verträge erzeugen“ liefert zwei PDFs, die der Vorlage entsprechen (Layout, Texte, Klauseln).
2. Vergütung eines Vertrags geändert, Vermerk sichtbar.
3. Scan hochgeladen, Seminar-Checkliste zeigt „Verträge unterschrieben“.
4. Person mit Teamkonto hat Vertragsdaten gespeichert: Folgevertrag ist vollständig vorbelegt.

## 6 Risiken und Hinweise
| Risiko | Gegenmaßnahme |
|---|---|
| **Rechtliche Wirksamkeit und Scheinselbstständigkeit** | Der Vertragstext bleibt wortgleich; das Portal ersetzt keine rechtliche Prüfung. Vorstand/Steuerberatung prüfen, ob die Klauseln zur Praxis passen (z. B. regelmäßige Einsätze). |
| **Reisekosten:** Die Vorlage verweist auf das **Bundesreisekostengesetz**; die Fahrtkostenabrechnung (Paket G3) rechnet **30 Cent je km** | Nach meinem Wissen sieht das BRKG für Kfz 20 Cent je km vor; Berlin hat ein eigenes Landesreisekostengesetz. Bitte mit der Steuerberatung klären und den Satz in den Einstellungen anpassen (Rückfrage Q12). |
| Sensible Daten (Steuernummer, IBAN) | Verschlüsselung, Zugriff nur am Vertrag, Löschfrist, kein Protokoll |
| PDF-Erzeugung auf Hosting | Test in A3, Fallback Druckansicht |
| Unterschrift auf Papier verzögert | Statusanzeige „offen“ und Erinnerung in der Aufgabenübersicht |

## 7 Offene Fragen
Beantwortet: Q11 (aus Kostenvoranschlag), Q12 (30 Cent, Vertragstext unverändert), Q13 (Teamende wählen die Umsatzsteuer), Q14 (nur drucken, Unterschrift an den Seminartagen). Offen bleibt nur Q15 (Kostenstellen).
