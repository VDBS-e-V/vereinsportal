# Paket F: SSO / OAuth mit dem Portal als Anbieter

Aufwand: **6 ET**. Ziel: Nextcloud, Moodle und weitere Systeme melden Nutzende **über das Portal** an. Das Portal bleibt die einzige Anmeldung (keine Anmeldung über Google/Microsoft). Alles ist **ohne Konsole** einrichtbar.

## 1 Ausgangslage
- `laravel/passport` ^13.7 ist installiert; `Modules/Sso/SsoServiceProvider` setzt Laufzeiten (Zugriff 1 Stunde, Refresh 30 Tage, persönliche Tokens 6 Monate). Test `SsoConfigurationTest` prüft den API-Guard.
- Es gibt **keine** Oberfläche für Clients, **keine** OIDC-Endpunkte (Discovery, Userinfo, ID-Token), **keine** Zustimmungsseite im Portal-Design, keine Schlüsselerzeugung ohne Konsole, keine Anleitungen.
- Konten tragen Zwei-Faktor (vorhanden), Rollen (`RoleAssignment`), Status (aktiv, gesperrt, in Löschung).

## 2 Entscheidungen

| Frage | Entscheidung |
|---|---|
| Protokoll | **OpenID Connect** (Authorization Code mit PKCE) auf Passport. Kein Password-Grant, kein Implicit. |
| Bibliothek | Passport 13 baut auf `league/oauth2-server`. Für OIDC wird geprüft: (a) Paket `steverhoades/oauth2-openid-connect-server` mit Passport 13 (Kompatibilität prüfen, Wartungsstand), (b) sonst eigener `IdTokenResponse` (Subklasse `BearerTokenResponse`, JWT mit `lcobucci/jwt`, das Passport ohnehin mitbringt). Entscheidung am Paketstart durch einen halben Tag Erprobung. |
| Scopes | `openid`, `profile` (Name, Anzeigename), `email` (Adresse, bestätigt), `roles` (Rollenschlüssel), `groups` (Bereiche als Gruppen für Nextcloud). **Keine** Adresse, kein Geburtsdatum, keine Telefonnummer. |
| Subject (`sub`) | stabile, nicht erratbare ID: `u_` + UUID im Konto (neue Spalte `users.public_uuid`), nicht die Datenbank-ID. |
| Wer verwaltet Clients? | Nur Administration (`oauth.manage` → Rolle Administration). Vorstand sieht eine Liste (nur lesen), kein Geheimnis. |
| Wer darf sich anmelden? | Je Anbindung einstellbare Rollen. **Standard für Nextcloud und Moodle:** Mitarbeitende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand (Antwort 18). Teamende und Mitglieder nicht, solange nicht freigegeben. |
| Gruppen und Teamordner | `groups`-Claim mit **stabilen Gruppennamen je Rolle** (`vdbs-mitarbeitende`, `vdbs-koordination`, `vdbs-bildungsreferenten`, `vdbs-geschaeftsstelle`, `vdbs-vorstand`). In Nextcloud ordnen Sie den Gruppen **Gruppenordner** mit jeweils eigenen Rechten zu (App „Team folders“). Das ist technisch der einfachste Weg (Antwort 17). |
| Zwei-Faktor | Gilt wie beim Portal-Login; der OAuth-Ablauf nutzt die bestehende Sitzung. |
| Abmeldung | Portal-Logout beendet Tokens; RP-initiated Logout (`end_session_endpoint`) leitet zurück. Back-Channel-Logout nur wenn Nextcloud/Moodle es nutzen (zweite Stufe). |

## 3 Datenmodell
- Vorhanden: `oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_auth_codes`, `oauth_device_codes` (Passport-Migrationen).
- Neu: `users.public_uuid` (char 36, unique, per Migration für alle Konten befüllt).
- Neu: `oauth_client_settings`: `client_id` (FK), `allowed_roles` json null, `description`, `contact`, `show_consent` bool (Standard ja; für interne Systeme ohne Zustimmungsseite), `created_by_user_id`.

## 4 Arbeitspakete

### F1 Schlüssel und Einrichtung ohne Konsole (1 ET)
- Die Erstinstallation (Paket A5, Schritt 7) erzeugt die Passport-Schlüssel (`passport:keys`). Zusätzlich Seite in der Systemsteuerung „Anbindungen › Schlüssel“: Status (vorhanden, Alter, Fingerabdruck des öffentlichen Schlüssels), **Erneuern** mit Passwortabfrage und Warnung („alle Anmeldungen laufen ab“).
- Speicherort `storage/oauth-*.key` (Dateirechte 600, nicht im Webroot). Schlüssel sind Teil der Sicherung (A8). Alternative für Hoster ohne Dateirechte: Schlüssel in der `.env` (`PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY`) – Passport unterstützt das.
- `users.public_uuid` anlegen und befüllen.

### F2 Verwaltung der Anbindungen (1,5 ET)
Bereich **Admins**, Seite „Anbindungen“ (`admin.oauth.*`, `can:oauth.manage`):
- **Liste:** Name, Client-ID, Art (vertraulich/öffentlich), Umleitungsadressen, zuletzt benutzt, Status.
- **Neu anlegen:** Name, Beschreibung, Umleitungsadressen (eine pro Zeile, nur `https://`, Ausnahme `http://localhost` im lokalen Betrieb), vertraulich ja/nein (Standard ja), erlaubte Rollen, Zustimmungsseite ja/nein. Nach dem Speichern **einmalige Anzeige** von Client-ID und Geheimnis mit Kopierknopf und Hinweis.
- **Bearbeiten:** Name, Adressen, Rollen. **Geheimnis erneuern** (zeigt neues einmalig). **Sperren/Entsperren** (widerruft Tokens). **Löschen**.
- Protokoll: Anbindung angelegt/geändert/gesperrt (ohne Geheimnis, ohne Adressen).
- Validierung der Umleitungsadressen: absolute URL, kein Fragment, keine Platzhalter, höchstens 10 je Client.

### F3 OIDC-Endpunkte (2 ET)
Routen unter dem Portal (Präfix `/oauth`, Passport liefert `/oauth/authorize`, `/oauth/token`):
- `GET /.well-known/openid-configuration`: `issuer`, `authorization_endpoint`, `token_endpoint`, `userinfo_endpoint`, `jwks_uri`, `end_session_endpoint`, unterstützte Scopes/Claims/Algorithmen (`RS256`), `code_challenge_methods_supported: ["S256"]`, `grant_types_supported: ["authorization_code","refresh_token"]`.
- `GET /oauth/jwks`: öffentlicher Schlüssel als JWK (`kid` = Fingerabdruck).
- ID-Token bei `openid`: Claims `iss`, `sub`, `aud`, `exp`, `iat`, `auth_time`, `nonce`, `email`, `email_verified`, `name`, `given_name`, `family_name`, `preferred_username` (Anzeigename), `roles`, `groups`.
- `GET|POST /oauth/userinfo` (Bearer): Claims je Scope. Gesperrte Konten bekommen 401.
- Abmeldung `GET /oauth/logout` mit `id_token_hint` und `post_logout_redirect_uri` (nur registrierte Adressen).
- Rollen- und Gruppenclaims: `roles` = Schlüssel der aktuell gültigen Rollen (`administration`, `board_member`, …), `groups` = Bereiche, in denen das Konto Seiten sieht (aus `PortalNavigation`). Namen sind stabil dokumentiert.
- Einschränkung der Anmeldung nach `allowed_roles` im Autorisierungsschritt (403-Seite „Für diese Anwendung nicht freigegeben“).

### F4 Zustimmung und Verbundene Anwendungen (1 ET)
- Passport-Autorisierungsansicht im Portal-Design (`resources/views/vendor/passport/authorize.blade.php`), in einfacher Sprache: „Nextcloud möchte: Ihren Namen sehen, Ihre E-Mail-Adresse sehen, Ihre Rolle im Verein sehen.“, Schaltflächen Erlauben/Ablehnen.
- Konto › **Verbundene Anwendungen**: Liste der freigegebenen Clients mit Datum, **Widerrufen** (löscht Tokens des Clients für das Konto).
- Sperrt der Verein ein Konto oder löscht es, werden dessen Tokens ungültig (Test).

### F5 Anleitungen und Tests (0,5 ET + Tests)
`docs/69_SSO_ANBINDUNG.md` mit Schritt-für-Schritt-Einrichtung:
- **Nextcloud:** App „OpenID Connect user backend“ (`user_oidc`): Anbieter anlegen mit Discovery-URL, Client-ID/Geheimnis, Zuordnung `sub` → Nutzer-ID, `name` → Anzeigename, `email`, `groups` → Gruppen, „Auto-Provisioning“ ein.
- **Moodle:** „OAuth 2 Service“ vom Typ „OpenID Connect“, Basis-URL = Portal, Zuordnung der Felder, Anmeldung per Knopf.
- Allgemein: Prüfliste für weitere Systeme (Umfragetool): Discovery, Scopes, Umleitungsadresse.
- Fehlerbilder: falsche Umleitung, abgelaufene Uhr (`exp`/`iat` Toleranz 60 s), Zertifikat, Schlüssel erneuert.

Tests (`OidcTest`):
1. Discovery liefert Pflichtfelder und richtige Adressen.
2. JWKS enthält den Schlüssel mit `kid`.
3. **Ende-zu-Ende:** Authorization Code mit PKCE → Token → ID-Token validierbar (Signatur mit JWKS, `nonce`, `aud`), Userinfo liefert Claims nach Scope.
4. Ohne PKCE wird bei öffentlichem Client abgelehnt.
5. Falsche Umleitungsadresse abgelehnt.
6. Gesperrtes/gelöschtes Konto: Token ungültig.
7. `allowed_roles`: Konto ohne Rolle bekommt 403, mit Rolle Zugang.
8. Geheimnis erscheint nur einmal, wird gehasht gespeichert (Passport-Standard).
9. Rechte: nur Administration verwaltet Clients; Vorstand liest Liste ohne Geheimnis.
10. Widerruf in „Verbundene Anwendungen“ löscht Tokens.
11. `claims` enthalten keine Adresse, kein Geburtsdatum, keine Telefonnummer (Negativtest).

## 5 Datenschutz
- Weitergabe nur der Claims nach Scope; Klartext-Zustimmung. Auftragsverarbeitung mit den Betreibern von Nextcloud/Moodle gehört in den Vertrag (nicht Teil des Codes).
- Tokens ablaufend (1 Stunde / 30 Tage), Aufräumen abgelaufener Tokens täglich: Befehl `passport:purge` in den Planer aufnehmen.

## 6 Abnahme (M4)
1. Anbindung „Nextcloud“ anlegen, Geheimnis einmalig sehen.
2. Anmeldung bei einer Test-Nextcloud über das Portal inklusive Zwei-Faktor.
3. Gruppen kommen an, Rollenbeschränkung greift.
4. Widerruf im Konto, Sperrung des Kontos: Zugriff weg.

## 7 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| OIDC-Paket nicht mit Passport 13 kompatibel | eigener `IdTokenResponse`, der Aufwand steht im Plan (+0,5 ET) |
| Uhrzeitabweichung zwischen Servern | Toleranz dokumentieren, NTP beim Hoster voraussetzen |
| Schlüsselverlust | Sicherung (A8), Erneuern-Funktion, Hinweis |
| Umleitungsadressen falsch | strenge Validierung, Testanmeldung auf der Anbindungsseite |

## 8 Offene Fragen
Siehe `01_OFFENE_FRAGEN.md`: R3 (Rolle Mitarbeitende), R4 (Adresse und Version von Nextcloud und Moodle, Moodle-Kurszuordnung).
