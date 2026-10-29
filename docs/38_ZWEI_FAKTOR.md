# Zwei-Faktor-Anmeldung (Modul Access)

## Ablauf der Anmeldung

1. `POST /login` prüft E-Mail und Passwort (`LoginRequest::validatedUser`, mit Begrenzung der Fehlversuche).
2. Hat das Konto eine bestätigte, nicht entfernte Methode, ist der Nutzer **noch nicht angemeldet**: Die Sitzung merkt sich
   nur `two_factor` (Nutzer-ID, „Angemeldet bleiben“, Ablauf nach 10 Minuten) und leitet auf `/zwei-faktor`.
   Ist E-Mail die Standardmethode, wird der Code sofort gesendet.
3. `POST /zwei-faktor` prüft den Code (Authenticator-App, E-Mail oder Wiederherstellungscode), höchstens 5 Fehlversuche je
   Nutzer und IP in 5 Minuten. Erst danach `Auth::login`, neue Sitzungs-ID und `last_login_at`.

## Passwortmanager

Das Code-Feld der Anmeldung ist für Passwortmanager als Zwei-Faktor-Feld erkennbar: `autocomplete="one-time-code"`,
Feldname und Id `otp` (`field-otp`), `inputmode="numeric"`, ein einzelnes Textfeld mit Länge 6, eigene Seite mit nur diesem
Feld und der Beschriftung „Sicherheitscode (Zwei-Faktor-Code)“. Damit ein Manager den Code ausfüllt, muss er den
App-Schlüssel kennen: beim Einrichten den Schlüssel (oder den Link „direkt in der App öffnen“) im Manager als
Einmalpasswort/TOTP-Feld des Eintrags hinterlegen.

## Methoden

| Methode | Prüfung |
|---|---|
| Authenticator-App (TOTP) | RFC 6238, SHA-1, 6 Stellen, 30 s, Toleranz ±1 Zeitfenster; jedes Zeitfenster gilt nur einmal (Cache) |
| E-Mail-Code | 6 Ziffern, 10 Minuten gültig, einmal nutzbar, neuer Code macht den alten ungültig, Versand höchstens einmal pro Minute |
| Wiederherstellungscode | 10 Codes `XXXXX-XXXXX`, einmal nutzbar, werden nur beim Erzeugen im Klartext angezeigt |

Gespeichert werden TOTP-Secrets verschlüsselt (APP_KEY) und alle Codes nur als HMAC-SHA-256-Hash (`CodeHasher`).

## Konto > Sicherheit (`/konto/sicherheit`)

App einrichten (Schlüssel als Text und `otpauth://`-Link, Bestätigung mit einem Code), E-Mail-Code einrichten,
Methoden entfernen und neue Wiederherstellungscodes erzeugen. Entfernen und neue Codes verlangen das Passwort.
Wiederherstellungscodes entstehen mit der ersten Methode und verfallen mit der letzten.

## Code

`app/Modules/Access/Services/` (`Totp`, `CodeHasher`, `RecoveryCodes`, `EmailChallenges`, `TwoFactorManager`),
Controller `LoginController`, `TwoFactorChallengeController`, `SecurityController`, Ansichten unter
`resources/views/access/two-factor/` und `resources/views/account/security/`. Tests: `tests/Unit/TotpTest.php`,
`tests/Feature/Access/TwoFactorLoginTest.php`, `SecurityTest.php`.

## Offen

- **QR-Code** beim Einrichten (braucht eine QR-Bibliothek, z. B. `bacon/bacon-qr-code`); bisher Schlüssel und Link.
- **Pflicht für alle oder bestimmte Rollen:** bisher freiwillig. Eine Pflicht wäre eine Middleware, die Nutzer ohne Methode
  auf `/konto/sicherheit` umleitet.
- **Gerät merken** (30 Tage) fehlt; das Muster in der Bibliothek zeigt die Checkbox bereits.
- **Protokoll** der Sicherheitsereignisse (Einrichten, Entfernen, Fehlversuche) wartet auf das Audit-Modul.
- **E-Mail-Vorlage** ist ein einfaches Blade-Template, bis das Vorlagensystem existiert.
