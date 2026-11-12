# 50 Öffentliche Seiten (Bereich Start)

Alle Seiten sind ohne Anmeldung erreichbar und stehen im Bereich Start (Zeile 2 des Kopfbereichs, siehe `docs/43_BEREICHE.md`). Sie nutzen die Bausteine der Design-Bibliothek
(Hero, Schnellzugriff, Chips, Teaser, Split, Akkordeon, Formulare, Stepper). Platzhalterbilder gibt es nur in der Design-Bibliothek, nicht auf diesen Seiten.

| Seite | Adresse | Inhalt |
|---|---|---|
| **Start** | `/` | Hero, Schnellzugriff (Über das Portal, Zugang zum Portal, Mein Konto/Anmelden, Hilfe), Bereiche, **Empfohlene Artikel**, Zugang, Kontakt |
| **Über das Portal** | `/ueber-das-portal` | „Unser Service-Portal“, Was erwartet Sie, Vorteile, **Häufig gestellte Fragen** (`#portal-faq`), Zugang |
| **Zugang zum Portal** | `/zugang-zum-portal` | Anmelden, Passwort vergessen, **Antrag auf Zugang** (mehrstufig) |
| **Kontakt** | `/kontakt` | **Kontaktformular** (Empfänger wählbar), optional Kontaktdaten aus `config/portal.php` |
| **Artikel** | `/artikel`, `/artikel/{slug}` | Liste (9 je Seite) und einzelner Artikel mit Bild und Quelle |

## Antrag auf Zugang (Zugangsantrag)

- **Formular** in vier Schritten (Einwilligung, Verbindung, Kontaktdaten, Prüfen und absenden). Mit JavaScript (`resources/js/wizard.js`) ein Schritt nach dem anderen mit Zusammenfassung,
  ohne JavaScript alle Schritte untereinander. Nach einem Fehler öffnet sich der erste Schritt mit Fehler.
- **Verbindung:** Teamer:in, Schüler:in, Lehrkraft, Partner:in. **Vereinsmitglieder stellen keinen Zugangsantrag**, sie wenden sich an den Vorstand (Hinweis auf der Seite; wer Mitglied werden will,
  nutzt „Mitglied werden“). Nachweise (PDF, JPEG, PNG; höchstens 5 Dateien à 5 MB) liegen im privaten Speicher.
  Geschlecht und Wunsch-Benutzername aus dem alten Formular entfallen: Das Portal meldet mit der E-Mail-Adresse an und kennt kein Geschlechtsfeld.
- **Schutz:** höchstens 5 Anträge je Stunde und Adresse, Köder-Feld. Gibt es zur E-Mail-Adresse schon ein Konto oder einen offenen Antrag, wird nichts angelegt; die Adresse bekommt eine neutrale
  Nachricht. Die Antwort auf der Seite ist immer dieselbe.
- **Eingang:** Eingangsbestätigung per E-Mail an die Person, Hinweis ohne personenbezogene Angaben an die Geschäftsstelle (`ACCESS_REQUESTS_NOTIFY_STAFF`).
- **Prüfung** (Bereich Geschäftsstelle → **Zugangsanträge**, `access_requests.view`/`access_requests.manage`: Geschäftsstelle und Vorstand): Liste mit Filter und Suche, Details mit Nachweisen,
  „In Prüfung nehmen“, **Annehmen** (Konto mit Einladung per E-Mail, optional Rolle Teamende, dann beginnt die Einarbeitung; vorhandene Person ohne Konto wird verknüpft) oder **Ablehnen**
  (E-Mail an die Person, Begründung freiwillig). Mit der Entscheidung werden die Nachweise gelöscht.
- **Aufbewahrung:** entschiedene Anträge 180 Tage, liegengebliebene offene 365 Tage (`ACCESS_REQUESTS_RETENTION_DAYS`, `ACCESS_REQUESTS_OPEN_RETENTION_DAYS`), täglicher Befehl
  `access-requests:prune`. Beim Löschen eines Kontos entfallen die Anträge dieser Adresse. Im allgemeinen Protokoll stehen nur Annahme und Ablehnung (ohne Namen im Inhalt).
- Die Links zu Nutzungsbedingungen und Datenschutzerklärung stehen in `config/portal.php` (`legal`, `LEGAL_TERMS_URL`, `LEGAL_PRIVACY_URL`); solange sie `#` sind, führen sie ins Leere.

## Kontaktformular

Vorname, Nachname, E-Mail, **Empfänger** (Allgemeine Anfrage, Vorstand, Portal-Support aus `config/portal.php` → `contact_form.recipients`, Adressen per `CONTACT_*_EMAIL`), Betreff, Inhalt, Einwilligung.
Die Nachricht geht nur per E-Mail an den Empfänger (Antworten an die absendende Person), im Portal wird nichts gespeichert. Schutz: höchstens 5 Nachrichten je 10 Minuten, Köder-Feld.

## Artikel

- **Verwaltung** (Bereich Geschäftsstelle → **Artikel**, `articles.manage`: Geschäftsstelle und Vorstand): Titel, Anreißer, Text (einfaches Markdown, kein eigenes HTML, keine unsicheren Links), Kategorie,
  Bild mit **Alternativtext** (Pflicht mit Bild) und **Bildquelle**, Status Entwurf/Veröffentlicht, „Veröffentlichen ab“ (Zukunft = geplant), „Auf der Startseite empfehlen“.
- **Öffentlich:** Nur veröffentlichte Artikel mit erreichtem Datum. Die Startseite zeigt bis zu drei (empfohlene zuerst, dann die neuesten), „Alle Artikel anzeigen“ führt zur Liste. Entwürfe und geplante
  Artikel sehen nur Berechtigte (als Vorschau).
- **Bilder** liegen im privaten Speicher und werden über `/artikel/{slug}/bild` ausgeliefert (kein `storage:link` nötig).
- Im Protokoll: Angelegt, geändert (mit Feldnamen), gelöscht.

## Offen

- Bilder für Hero und Abschnitte der Startseite (der Entwurf kennt Fotos; im Portal gibt es bisher nur den Schriftzug) und eigene Bereichs-/Systemlinks (Webmail, Moodle, Nextcloud …) in der Kopfzeile.
- Texte der Nutzungsbedingungen und Datenschutzerklärung als Seiten im Portal.
