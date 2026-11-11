# Lokal ausprobieren (Rollen, Bereiche, Anmeldung)

Für Ihren Rechner (Windows, XAMPP, `php artisan serve` bzw. Herd).

## 1 Testkonten für jede Rolle
```
php artisan portal:update          (einmal, legt Rollen und Tabellen an)
php artisan portal:demo-users      (legt <rolle>@demo.test an, Passwort: Demo-Passwort-2026)
```
Der Befehl läuft nur mit `APP_ENV=local`. Er ist wiederholbar und setzt das Passwort neu. Eigenes Passwort: `--password=...`.

Konten: `administration@demo.test`, `administration-staff@demo.test`, `board-member@demo.test`, `coordination@demo.test`, `education-coordination@demo.test`, `team@demo.test`, `member@demo.test`, `school@demo.test`, `employee@demo.test`, `managing-director@demo.test`, `treasury@demo.test`.

**Zwei-Faktor:** Für Administration, Geschäftsstelle, Vorstand, Geschäftsführung und Kassenführung ist er Pflicht. Zum schnellen Ausprobieren in der `.env` `TWO_FACTOR_REQUIRED=false` setzen und `php artisan config:clear`. Zum Prüfen der Pflicht auf `true` lassen: Nach der Anmeldung erscheint die Einrichtung (Authentifizierungs-App oder E-Mail-Code; E-Mails landen lokal im Log `storage/logs/laravel.log`, wenn `MAIL_MAILER=log`).

Tipp: Zwei Browserprofile (oder ein privates Fenster) nutzen, um gleichzeitig als Vorstand und als Geschäftsstelle angemeldet zu sein.

## 2 Abnahme Paket I
1. Als `board-member@demo.test`: Admins gibt es nicht, aber Geschäftsstelle › Nutzer › Person wählen › „Rolle vergeben“: **Geschäftsführung** und **Kassenführung** stehen zur Auswahl.
2. Als `administration-staff@demo.test`: dieselbe Seite: **Mitarbeitende** steht zur Auswahl, **Geschäftsführung/Kassenführung nicht**.
3. Als `employee@demo.test`: oben erscheint der Bereich **Mitarbeitende** mit Übersicht und (wenn `PORTAL_NEXTCLOUD_URL` gesetzt ist) dem Nextcloud-Link.
4. Als `managing-director@demo.test`: Bereich Mitarbeitende sichtbar; bei `TWO_FACTOR_REQUIRED=true` Weiterleitung zur Zwei-Faktor-Einrichtung.

## 2a Anträge ausprobieren
- `team@demo.test` › Bereich Teamende › **Anträge**: Kostenrückerstattung oder Fahrtkosten.
- `member@demo.test` › Bereich Mitglieder › **Anträge**: ohne Seminar und Kostenstelle.
- `employee@demo.test` › Bereich Mitarbeitende › **Anträge**: auch Materialbestellung.
- `administration-staff@demo.test` › Geschäftsstelle › **Anträge**: prüfen und freigeben (bis 50 €); darüber `managing-director@demo.test` oder `board-member@demo.test`.

## 3 Anmeldung bei anderen Systemen (Paket F) lokal prüfen
Ohne eigene Nextcloud geht das mit dem Browser:
1. Admins › Anbindungen › Schlüssel erzeugen, dann „Neue Anbindung“: Name „Test“, Umleitungsadresse `https://oidcdebugger.com/debug`, Zustimmungsseite **an**, Rollen z. B. Koordination. Client-ID notieren.
2. Auf <https://oidcdebugger.com> die Adresse `http://127.0.0.1:8000/oauth/authorize` eintragen, Client-ID, Scope `openid profile email roles groups`, Response type `code`, PKCE `S256`. „Send request“.
3. Im Portal als `coordination@demo.test` anmelden und „Erlauben“. Die Seite zeigt den Code.
4. Token abrufen (PowerShell, Code und Verifier ersetzen):
   `curl.exe -X POST http://127.0.0.1:8000/oauth/token -d "grant_type=authorization_code&client_id=...&client_secret=...&redirect_uri=https://oidcdebugger.com/debug&code=...&code_verifier=..."`
5. Mit dem `access_token`: `curl.exe -H "Authorization: Bearer ..." http://127.0.0.1:8000/oauth/userinfo`. Das ID-Token lässt sich auf <https://jwt.io> mit dem Schlüssel von `/oauth/jwks` prüfen.
Mit `Konto › Verbundene Anwendungen` widerrufen Sie die Freigabe; danach liefert Userinfo 401.

## 4 Automatische Tests
`php -d display_errors=1 vendor\pestphp\pest\bin\pest` (alle) oder einzeln `... pest tests\Feature\AccessGovernance\EmployeeRolesTest.php`.
