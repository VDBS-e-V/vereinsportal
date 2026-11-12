# Konzept: Rundschreiben und Mitteilungen weiter ausreifen

Stand: 2. November 2026. Dieses Dokument ist ein **Konzept und Plan**, kein Umsetzungsbericht. Es baut auf dem heutigen Stand auf (`docs/63_RUNDSCHREIBEN.md`).

## Inhalt
1. Ausgangslage
2. Recherche: Beispiele aus der Praxis, Standards und Recht
3. Was daraus für uns folgt (Lücken gegenüber dem heutigen Stand)
4. Merkmalsliste (Anforderungen)
5. Offene Entscheidungen
6. Plan
7. Quellen und Grenzen der Recherche

---

## 1. Ausgangslage

Heute kann der Vorstand und die Geschäftsstelle an Gruppen schreiben (Mitglieder, Teamende, Schulen, Koordination, Bildungsreferenten, Vorstand, Geschäftsstelle), frei oder auf Basis einer Vorlage, mit Platzhaltern, Vorschau und Testmail. Die Koordination schreibt an Teamende zu Seminaren mit einer Vorlage der Geschäftsstelle und eigenem Textabschnitt.

Stärken, die bleiben sollen:
- Jede Person bekommt eine **eigene** E-Mail. Es gibt keinen offenen Verteiler und kein BCC-Risiko.
- Platzhalter-System mit Prüfung auf Tippfehler, Maskierung der Werte, Anrede-Fallback.
- Gespeichert werden nur Betreff, Text, Gruppen und Zahlen, nie die Empfängerliste.
- Abbestellen für Teamende und Schulen, Pflichtpost für Mitglieder.

Bekannte Schwächen (aus dem Betrieb abgeleitet):
- Versand **synchron im Seitenaufruf**: Zeitlimit, kein Wiederholen, keine Fortschrittsanzeige.
- Es gibt **keinen Zustellstatus pro Empfänger**. Man weiß nur „n gesendet, m fehlgeschlagen“.
- Kein Entwurf, kein Zeitplan, keine Freigabe.
- Kein **Abmelde-Link** in der Mail und keine Kopfzeilen `List-Unsubscribe`.
- Keine Unterdrückungsliste (harte Fehlzustellungen, Beschwerden).
- Keine Anhänge, keine Textversion, keine Barrierefreiheitsprüfung.
- Empfänger nur nach festen Gruppen, nicht nach Filtern, Ausschlüssen oder Einzelpersonen.
- Für die Einladung zur Versammlung fehlt ein **Nachweis** (Versanddatum, Fristrechner, Kopie).

---

## 2. Recherche

### 2.1 Beispiele aus der Praxis

| Beispiel | Was es zeigt | Was wir mitnehmen |
|---|---|---|
| **Webling** (Vereinssoftware, CH) | E-Mail-Vorlagen und Serienbriefe, Platzhalter für alle Daten eines Mitglieds oder einer Rechnung, eigener Editor. | Vorlagenbibliothek und Platzhalter über Datenfelder. Haben wir schon. Ausbaubar: mehr Datenfelder (z. B. Beitragsstand, Eintrittsdatum). |
| **OMR-Bewertung „Unsere Vereinssoftware“** | Serienbriefe mit Variablen und Textbausteinen, Versand an alle oder einzeln je Mitglied, individuelle Anreden. | **Textbausteine** (wiederverwendbare Absätze) und individuelle Anreden. |
| **DFBnet Verein** | Funktion „Mitglied anschreiben“ direkt aus dem Mitgliederstamm. | **Kontextaktion**: Schreiben aus einer Liste oder Detailseite starten (z. B. aus einer Mitgliedschaft, einem Seminar, einer Schule). |
| **CiviCRM / CiviMail** (Open-Source-CRM für Vereine) | Pflicht-Token für Anschrift und Abmeldung, Zustände (z. B. geplant), **Ausschlussgruppen** bei der Empfängerwahl, Bounce-Erfassung (sofort und später), Abmeldung je Gruppe oder vollständig, Berichte zu Zustellung, Öffnungen, Klicks. | Lebenszyklus eines Schreibens, Ausschlussgruppen, Bounce-Tabelle, **Abmeldung je Gruppe** statt alles-oder-nichts, Standardwert bei fehlenden Platzhalter-Werten (`{vorname\|Freund}`). |
| **Brevo / Mailchimp** (E-Mail-Marketing) | Segmentierung, Merge-Tags, Double-Opt-in, Tests mit Beispieldaten vor dem Versand, Pflege der Liste vor der Segmentierung. | Segmente und Testen mit Beispieldaten. Aber: Newsletter-Marketing ist nicht unser Fall, wir haben **Vereinspost und Organisationsmitteilungen**. Tracking-Pixel und Kampagnenautomatik übernehmen wir nicht. |

### 2.2 Standards und Anforderungen der Postfachanbieter

- **List-Unsubscribe (RFC 2369) und One-Click (RFC 8058).** Die Kopfzeile `List-Unsubscribe` trägt einen HTTPS-Link (optional zusätzlich `mailto:`), `List-Unsubscribe-Post` den Wert `List-Unsubscribe=One-Click`. Der Postfachanbieter sendet dann einen POST an unseren Endpunkt. Abmeldungen sollen binnen etwa 48 Stunden wirksam sein. Beide Kopfzeilen gehören in die DKIM-Signatur.
- **Gmail und Yahoo seit Februar 2024.** Absender mit hohem Volumen (Gmail: etwa 5 000 Nachrichten pro Tag an Gmail-Adressen) brauchen SPF, DKIM und DMARC mit passender Absenderdomain, One-Click-Abmeldung und eine Beschwerderate unter 0,3 %. Als gesund gilt unter 0,1 %. Unser Volumen liegt weit darunter, **die technischen Regeln sind trotzdem die Messlatte für gute Zustellung**: sie sind billig zu erfüllen und verhindern Spam-Ordner.
- **Bounces und Unterdrückung.** Harte Fehlzustellungen führen zur sofortigen Sperre der Adresse. Weiche werden begrenzt wiederholt und erst bei anhaltendem Fehler gesperrt (üblich: etwa fünf Fehlversuche in Folge, das ist eine Konvention einzelner Anbieter, kein Standard). Beschwerden werden sofort gesperrt. Eine Bounce-Rate über 2 bis 3 % gilt bei einzelnen Anbietern als Warnschwelle.
- **E-Mail-Barrierefreiheit.** Es gilt sinngemäß WCAG 2.1/2.2 Stufe AA: Alternativtexte, ausreichender Kontrast, aussagekräftige Linktexte, sauber gegliederte Struktur, Sprache im `<html>`-Element, lebender Text statt Textbildern. Der Bericht des Email Markup Consortium 2025 findet bei fast allen untersuchten HTML-Mails schwere Mängel. Eine **Textversion** (multipart/alternative) ist verbreitete Praxis, in den gelesenen Quellen aber nicht als Barrierefreiheitsmaßnahme belegt.
- **Versand technisch in Laravel.** Mails in die Warteschlange legen, Rate-Limit per Job-Middleware, Wiederholungen mit Wartezeit statt fester Versuchszahl. Bounces kommen über Webhooks des Versanddienstes, nicht aus Laravel selbst.

### 2.3 Recht (Orientierung, keine Rechtsberatung)

- **Form der Einladung zur Mitgliederversammlung.** Nach § 32 BGB regelt die **Satzung** Form und Frist. Steht dort „schriftlich“, genügt nach OLG Hamm in der Regel eine E-Mail, außer die Satzung zeigt einen entgegenstehenden Willen. „Textform“ (§ 126b BGB) erlaubt E-Mail ausdrücklich. „Per Brief“ schließt E-Mail aus. Ohne Satzungsregelung braucht es die Zustimmung der Mitglieder zur E-Mail-Einladung.
- **Frist und Nachweis.** Die Einladungsfrist beginnt in der Regel mit dem **Abgang** (Versanddatum), nicht mit dem Zugang. Verbände raten zu Sendebestätigung, Aufbewahrung der versandten Kopie und Dokumentation. Maßgeblich ist meist die zuletzt mitgeteilte Adresse.
- **Offene Verteiler.** Sichtbare Adressen in An oder CC brauchen eine Rechtsgrundlage; Aufsichtsbehörden haben Bußgelder verhängt. Empfehlung: BCC oder, wie bei uns, **Einzelversand**.
- **Newsletter.** Für Nicht-Mitglieder braucht es Einwilligung (Double-Opt-in üblich, Widerruf jederzeit, zusätzlich UWG). Einladungen an Mitglieder stützen sich auf die Mitgliedschaft; das ist eine Einordnung der Recherche, die der Verein mit seiner Satzung und dem Datenschutz abgleichen sollte.

---

## 3. Was daraus für uns folgt

1. **Zustellung belegen** (Versanddatum, Status je Empfänger, Fehlergrund) ist für die Einladung zur Versammlung rechtlich relevant und für alle Schreiben nützlich.
2. **Abmelden sauber lösen**: Link im Fuß, `List-Unsubscribe`/One-Click, Abmeldung **je Art von Mitteilung** statt alles oder nichts. Pflichtpost (Versammlung) bleibt ausgenommen.
3. **Hintergrundversand** mit Fortschritt, Wiederholung und Drosselung statt Seitenaufruf.
4. **Unterdrückungsliste** für harte Fehlzustellungen und Beschwerden, damit schlechte Adressen nicht immer wieder angeschrieben werden.
5. **Entwurf, Zeitplan, Freigabe** (Vier-Augen) für Schreiben an viele Personen.
6. **Empfängerwahl erweitern**: Ausschlüsse, Einzelpersonen, Filter, Kontextstart aus Seminar, Schule, Mitgliedschaft.
7. **Inhalt**: Textbausteine, Standardwerte für Platzhalter, Anhänge, Textversion, Prüfung auf Barrierefreiheit.
8. **Datensparsam bleiben**: kein Öffnungs- oder Klick-Tracking als Standard. Berichte nur über Zustellung.

---

## 4. Merkmalsliste

Stufen: **M** muss, **S** soll, **K** kann. Jedes Merkmal ist so formuliert, dass ein Test es prüfen kann.

### A. Rechtssicherheit und Nachweis
- A1 (M) Jeder Versand an Mitglieder wird mit **Versandzeitpunkt, Betreff, Text und Zahl der Empfänger** unveränderlich festgehalten.
- A2 (M) Für jede Adresse gibt es einen **Zustellstatus** (in Warteschlange, übergeben, fehlgeschlagen mit Grund, gesperrt). Die Adresse selbst erscheint nur für Berechtigte und wird nach einer Frist gelöscht.
- A3 (S) Bei Einladungen zur Versammlung zeigt das Portal die **Einladungsfrist** (Satzungswert, einstellbar) und warnt, wenn Termin und Versanddatum sie unterschreiten.
- A4 (S) Es gibt eine **Versandbescheinigung** (PDF oder Seite) mit Datum, Betreff, Text und Zahl der Zugestellten.
- A5 (S) Mitglieder ohne Adresse oder mit gesperrter Adresse werden **gelistet**, damit der Vorstand sie postalisch einladen kann.
- A6 (M) Pflichtpost (Versammlung) ist **nicht abbestellbar** und als solche gekennzeichnet. Alles andere ist abbestellbar.

### B. Empfänger
- B1 (M) Gruppen wie heute, jede Adresse einmal.
- B2 (S) **Ausschlussgruppen**: „alle Teamenden außer …“.
- B3 (S) **Einzelpersonen** zusätzlich auswählen (Suche nach Name).
- B4 (S) **Kontextstart**: Schreiben aus Seminar, Schule, Veranstaltung oder Mitgliedschaft mit vorbelegten Empfängern.
- B5 (K) **Filter** (z. B. Teamende mit offener Einarbeitung, Schulen eines Bundeslandes).
- B6 (M) Vor dem Senden steht die **Empfängerzahl je Gruppe und gesamt** und die Zahl der Abbestellten und Gesperrten.
- B7 (K) Gespeicherte Empfängerlisten (Segmente) mit Namen.

### C. Inhalt und Editor
- C1 (M) Freier Editor mit Platzhaltern, Vorschau, Testmail (haben wir).
- C2 (S) **Standardwerte**: `{vorname|Mitglied}` greift, wenn der Wert fehlt.
- C3 (S) **Textbausteine** (wiederverwendbare Absätze, z. B. Grußformel, Datenschutzhinweis), in Vorlagen und Schreiben einfügbar.
- C4 (S) **Einfügen von Platzhaltern per Klick** im Editor.
- C5 (S) **Anhänge** (PDF, Bilder bis Größenlimit), Virenprüfung nicht nötig, aber Typ- und Größenprüfung.
- C6 (M) Jede Mail enthält automatisch eine **Textversion** (aus dem HTML erzeugt).
- C7 (S) **Barrierefreiheitsprüfung** vor dem Senden: Alternativtexte, Linktexte, Überschriftenstruktur, Kontrast der Standardvorlage, Sprache gesetzt.
- C8 (M) Pflicht-Fuß: Absender (Verein, Anschrift), Hinweis, warum man die Mail bekommt, Abmeldung (außer Pflichtpost).
- C9 (K) Mehrsprachige Vorlagen.
- C10 (K) Versionsverlauf der Vorlagen.

### D. Versand und Zeitplanung
- D1 (M) Versand im **Hintergrund** (Warteschlange), Seitenaufruf kehrt sofort zurück.
- D2 (M) **Fortschrittsanzeige** (gesendet, offen, fehlgeschlagen) und Abbruch eines laufenden Versands.
- D3 (M) **Drosselung** und **Wiederholung** mit Wartezeit. Keine Doppelzustellung bei Wiederholung.
- D4 (S) **Entwurf** speichern und später weiterbearbeiten.
- D5 (S) **Zeitplan**: Versand zu einem späteren Zeitpunkt.
- D6 (S) **Freigabe (Vier-Augen)** für Schreiben an mehr als eine einstellbare Zahl von Personen oder an Mitglieder: Entwurf von A, Freigabe durch B.
- D7 (S) Antwortadresse (**Reply-To**) wählbar (z. B. vorstand@, geschaeftsstelle@), Absendername je Bereich.
- D8 (K) Wiederholung eines Schreibens an nicht Erreichte.

### E. Zustellqualität
- E1 (M) **SPF, DKIM, DMARC** der Absenderdomain sind eingerichtet und die Systemsteuerung zeigt den Stand (Hinweis, kein Sendestopp).
- E2 (M) **List-Unsubscribe** und **One-Click** (RFC 8058) in allen abbestellbaren Mails.
- E3 (M) **Unterdrückungsliste**: Harte Bounces und Beschwerden sperren die Adresse für Rundschreiben, mit Einsicht und manueller Freigabe.
- E4 (S) **Bounce-Erfassung** über Webhook oder Postfach des Versanddienstes.
- E5 (S) Warnung bei hoher Fehlerquote eines Versands.

### F. Abmeldung und Einstellungen
- F1 (M) Abmeldung **je Art** (Seminar-Mitteilungen, Schulmitteilungen, allgemeine Rundschreiben) statt alles oder nichts.
- F2 (M) Abmeldung ohne Anmeldung per **signiertem Link** (One-Click) und im Konto.
- F3 (S) Bestätigungsseite nach Abmeldung mit Wiederanmelden.
- F4 (M) Abmeldungen wirken **sofort** für den nächsten Versand (Frist des Anbieters: 48 Stunden).

### G. Berichte
- G1 (M) Je Schreiben: Zahl gesendet, zugestellt, fehlgeschlagen, gesperrt, abbestellt.
- G2 (S) **Kein** Öffnungs- und Klick-Tracking als Standard (Datenschutz). Optional und abschaltbar nur nach Beschluss.
- G3 (S) Übersicht über Fehlerquote, Abmeldungen und gesperrte Adressen im Zeitverlauf.

### H. Rollen und Datenschutz
- H1 (M) Wer was darf, bleibt in Berechtigungen (`mailings.send`, `mailings.members`, `mailings.team`, `mail_templates.manage`), neu: `mailings.approve`.
- H2 (M) Jede Aktion im Audit-Protokoll ohne Adressen und ohne Textinhalt.
- H3 (M) Zustellstatus je Adresse wird nach einer **einstellbaren Frist** gelöscht (Standard 90 Tage). Betreff, Text und Zahlen bleiben.
- H4 (S) Personenbezogene Platzhalter-Werte werden nie im Protokoll gespeichert.

### I. Betrieb und Qualität
- I1 (M) Fehlgeschlagene Zustellungen erscheinen im Fehlerprotokoll der Systemsteuerung, Warteschlange einsehbar.
- I2 (M) Alle Merkmale mit Tests. Neue Ansichten im Designsystem, WCAG 2.2 AA.
- I3 (S) Doku für Vorstand und Geschäftsstelle in einfacher Sprache (wann welche Gruppe, Frist, Abmeldung).

---

## 5. Offene Entscheidungen

1. **Versanddienst.** Heute `MAIL_MAILER` nach Wahl. Für Warteschlange, Bounces und gute Zustellung empfiehlt sich ein Dienst mit Webhooks (z. B. Postmark, Mailgun, Amazon SES) oder der eigene Server mit Postfach für Rückläufer. Wo läuft das Portal produktiv?
2. **Satzung.** Was steht zur Einladung zur Mitgliederversammlung (Form, Frist, E-Mail)? Davon hängen A3, A5 und der Pflichtcharakter ab.
3. **Freigabe.** Soll jedes Schreiben an Mitglieder von einer zweiten Person freigegeben werden, oder nur ab einer Zahl?
4. **Tracking.** Reicht „zugestellt oder nicht“, oder wollen Sie Öffnungen sehen? Empfehlung: nein.
5. **Anhänge.** Erlaubt, mit welchen Typen und welcher Größe? Empfehlung: PDF und Bilder bis 5 MB gesamt.
6. **Gespeicherte Segmente und Filter** (B5, B7): jetzt oder später?
7. **Absender.** Gemeinsame Absenderadresse oder je Bereich (Vorstand, Geschäftsstelle, Koordination)?

---

## 6. Plan

Die Reihenfolge folgt dem Nutzen und den Abhängigkeiten. Jede Phase ist für sich auslieferbar. Aufwand grob in Arbeitsschritten wie bisher (S klein, M mittel, L groß).

### Phase 1: Zustellung zuverlässig und belegbar (D1–D3, A1, A2, G1, I1) · L
- Warteschlange statt Seitenaufruf: ein Job je Schreiben, Teiljobs je Empfängerpaket, Drosselung, Wiederholung mit Wartezeit.
- Tabelle `mailing_deliveries` (Schreiben, Adresse, Status, Fehlergrund, Zeitpunkte), Aufräumen nach Frist (H3).
- Fortschrittsseite, Abbruch, Bericht je Schreiben.
- Abhängigkeit: Entscheidung 1 (Versanddienst und Worker). Lokal genügt `queue:work`.

### Phase 2: Abmelden und Unterdrücken (E2, E3, F1–F4, C8, A6) · L
- Abmeldelink (signiert), One-Click-Endpunkt, Kopfzeilen `List-Unsubscribe` und `List-Unsubscribe-Post`.
- Abmeldung je Art; Konto-Einstellungen anpassen; Pflichtpost gekennzeichnet.
- Unterdrückungsliste mit Ansicht und Freigabe.

### Phase 3: Entwürfe, Zeitplan, Freigabe (D4–D7, H1) · M
- Entwurfsstatus, geplanter Versand (Planer-Aufgabe), Berechtigung `mailings.approve`, Vier-Augen-Regel einstellbar.
- Reply-To und Absendername je Bereich.

### Phase 4: Nachweis für Versammlungen (A3–A5) · M
- Satzungsfrist als Einstellung, Fristwarnung, Bescheinigung, Liste der nicht per E-Mail Erreichbaren.
- Abhängigkeit: Entscheidung 2.

### Phase 5: Empfänger ausbauen (B2–B7) · M bis L
- Ausschlussgruppen, Einzelpersonen, Kontextstart aus Seminar, Schule, Veranstaltung, Mitgliedschaft; später Filter und gespeicherte Segmente.

### Phase 6: Inhalt ausbauen (C2–C7) · M
- Standardwerte, Textbausteine, Klick-Einfügen, Anhänge, Textversion, Barrierefreiheitsprüfung.

### Phase 7: Zustellqualität und Bounces (E1, E4, E5, G3) · M
- Prüfung von SPF/DKIM/DMARC in der Systemsteuerung (Hinweis), Webhook für Bounces und Beschwerden je Versanddienst, Warnung bei hoher Fehlerquote, Verlauf.
- Abhängigkeit: Entscheidung 1.

### Phase 8: Feinschliff (C9, C10, D8, I3) · S bis M
- Versionsverlauf, Wiederholung an nicht Erreichte, Doku in einfacher Sprache.

**Empfohlener Start:** Phase 1 und 2 zusammen, weil Zustellstatus und Abmeldung die Grundlage für alle weiteren Merkmale sind und die größte rechtliche und praktische Wirkung haben. Danach Phase 4 (Nachweis), sobald die Satzung geklärt ist.

---

## 7. Quellen und Grenzen der Recherche

Die Recherche lief über Websuche. **Die Originalseiten ließen sich aus dieser Umgebung nicht abrufen** (Hosts wie support.google.com, docs.civicrm.org und lsv-sh.vibss.de waren gesperrt). Die Angaben stützen sich deshalb auf Zusammenfassungen der Suchergebnisse und **müssen vor verbindlichen Entscheidungen gegen die Originale geprüft werden**, besonders Zahlen (Spam-Rate, Mengen) und die Rechtsauskunft.

Genutzte Quellen (Auswahl):
- Vereinssoftware und Funktionen: [Webling, Kommunikation](https://support.webling.ch/hc/de/articles/4407807792273-Webling-Tutorial-Thema-3-Kommunikation), [OMR, Unsere Vereinssoftware](https://omr.com/en/reviews/product/unsere-vereinssoftware), [DFBnet Verein](https://portal.dfbnet.org/fileadmin/content/downloads/DFBnet_Verein/Newsletter/DFBnetVerein_Newsletter_8-4-0_22-02-2024.pdf), [Brevo, CRM für Vereine](https://www.brevo.com/de/crm-fuer-vereine/)
- CiviCRM: [CiviMail Reference](https://docs.civicrm.org/dev/en/stable/framework/civimail/)
- Mailchimp-Konzepte (Merge-Tags, Segmente, Double-Opt-in): [Drip, Merge Tags](https://www.drip.com/email-marketing-glossary/merge-tags), [Mailchimp, Zielgruppendaten](https://mailchimp.com/de/4-steps-use-audience-data-to-drive-engagement/)
- Absenderanforderungen Gmail/Yahoo: [Bird, Gmail & Yahoo requirements](https://bird.com/docs/knowledge-base/deliverability/gmail-yahoo-requirements), [Close, Bulk Sender Requirements](https://help.close.com/technical-support/email-deliverability/requirements-for-bulk-email-senders)
- One-Click-Abmeldung: [Certified Senders Alliance, RFC 8058](https://certified-senders.org/wp-content/uploads/2017/07/CSA_one-click_list-unsubscribe.pdf), [Customer.io, Custom Unsubscribe Links](https://docs.customer.io/journeys/custom-unsubscribe-links/)
- Barrierefreiheit E-Mail: [Mailosaur, WCAG 2.2 für E-Mail](https://mailosaur.com/blog/email-accessibility-a-practical-guide-to-wcag-2-2), [NSW Digital, Accessibility for email developers](https://email.digital.nsw.gov.au/development/accessibility-for-email-developers)
- Bounces und Unterdrückung: [Batch, Email bounces](https://doc.batch.com/guides-and-best-practices/email-deliverability/list-hygiene-and-recipients-management/email-bounces-types-causes-and-handling), [Microsoft, Suppression lists](https://learn.microsoft.com/en-us/dynamics365/customer-insights/journeys/suppression-lists)
- Laravel-Versand: [Freek Van der Herten, Job-Middleware zur Drosselung](https://dev.to/freekmurze/a-job-middleware-to-rate-limit-jobs-18bi), [laravel-mail-batch](https://packagist.org/packages/1994/laravel-mail-batch), [laravel-mail-throttle](https://packagist.org/packages/ctsoftwarellc/laravel-mail-throttle)
- Vereinsrecht: [IWW, OLG Saarbrücken: Einladung per E-Mail](https://www.iww.de/vb/vereinsrecht/mitgliederversammlung-olg-saarbruecken-erleichtert-einladung-zur-mitgliederversammlung-per-e-mail-b67639), [Haufe, E-Mail genügt Schriftformerfordernis](https://www.haufe.de/recht/weitere-rechtsgebiete/wirtschaftsrecht/e-mail-genuegt-schriftformerfordernis-in-der-vereinssatzung_210_328560.html), [VIBSS, Form der Einberufung](https://lsv-sh.vibss.de/vereinsmanagement/recht/vereinsrecht/mitgliederversammlung/die-form-der-einberufung?type=123), [verbandsbüro, Einladung zur Mitgliederversammlung](https://www.verbandsbuero.de/anleitung-mitgliederversammlung-einladen/)
- Datenschutz: [e-recht24, offener E-Mail-Verteiler](https://www.e-recht24.de/online-marketing/11258-offener-email-verteiler-dsgvo.html), [Haufe, Bußgeld für offene Verteiler](https://www.haufe.de/compliance/bussgeld-e-mail-adressen-fuer-alle-empfaenger-sichtbar_230128_186302.html), [LfDI Rheinland-Pfalz, Datenschutz im Verein](https://datenschutz87.typo3web03.rlp.de/fileadmin/lfdi/Dokumente/Orientierungshilfen/Datenschutz_im_Verein__DS-GVO__-_Kompakt.pdf), [Brevo, E-Mail-Datenschutz und DSGVO](https://www.brevo.com/de/blog/email-datenschutz-und-dsgvo/)

Rechtliche Hinweise in diesem Dokument sind Orientierung für die Planung und ersetzen keine Beratung durch eine Vereinsrechtlerin oder einen Datenschutzbeauftragten.
