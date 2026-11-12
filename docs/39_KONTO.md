# Konto und Zugang (Modul Access)

Alle Konto-Seiten stehen unter `/konto` und brauchen eine Anmeldung. Gemeinsamer Rahmen: `<x-account.shell>` mit Seitenkopf
und der Tab-Leiste Profil | E-Mail | Passwort | Sicherheit | Sitzungen.

| Seite | Adresse | Inhalt |
|---|---|---|
| Profil | `/konto/profil` | Stammdaten der Person; Telefon und Anschrift änderbar, Name und Geburtsdatum nur durch die Geschäftsstelle |
| E-Mail | `/konto/email` | Änderung mit Bestätigungslink an die **neue** Adresse (signiert, 24 h, nur angemeldet) und Hinweis an die alte; ein neuer Antrag ersetzt den alten; höchstens 3 Anträge pro Stunde |
| Passwort | `/konto/passwort` | aktuelles Passwort erforderlich, neue Regel (siehe unten); beendet alle anderen Sitzungen |
| Sicherheit | `/konto/sicherheit` | Zwei-Faktor, siehe `38_ZWEI_FAKTOR.md` |
| Sitzungen | `/konto/sitzungen` | Liste der Geräte (Sitzungstabelle bei Treiber `database`), „alle anderen abmelden“ mit Passwort |

Ohne Anmeldung: `/passwort-vergessen` (Link per E-Mail, die Antwort verrät nie, ob ein Konto existiert) und
`/passwort-zuruecksetzen/{token}` (60 Minuten gültig, einmal nutzbar).

## Passwort-Regel

Mindestens 12 Zeichen mit Groß- und Kleinbuchstaben und einer Ziffer (`Password::defaults()` in `AppServiceProvider`).
Eine Prüfung gegen bekannte Datenlecks (`uncompromised()`) ist nicht aktiv, weil sie externe Abfragen braucht.

## Sitzungsversion

`users.session_version` beendet Sitzungen unabhängig vom Sitzungs-Treiber: Die Middleware `EnsureSessionVersion` (an die
Gruppe `web` angehängt) vergleicht die in der Sitzung gemerkte Version mit der des Kontos. Erhöht wird sie bei
Passwortwechsel, Passwort-Zurücksetzen, E-Mail-Wechsel und „alle anderen abmelden“. Die aktuelle Sitzung übernimmt die neue
Version und bleibt angemeldet; Sitzungen ohne gemerkte Version übernehmen die aktuelle.

## Tests

`tests/Feature/Access/AccountTest.php`, `PasswordResetTest.php`.

## Offen

- Konto löschen (mit Widerrufsfrist) und der Austritt gehören zum Offboarding.
- Profilbild (`avatar_path`) wird noch nicht hochgeladen.
- Die E-Mail-Vorlagen sind einfache Blade-Dateien unter `resources/views/mail/access/`, bis das Vorlagensystem existiert.
