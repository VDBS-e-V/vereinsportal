# Anbindungen (Paket F, Teil 1)

Das Portal ist die einzige Anmeldung. Nextcloud, Moodle und weitere Systeme melden Nutzende über das Portal an (OpenID Connect, Authorization Code mit PKCE auf Laravel Passport).

## Teil 1: Verwaltung
- **Admins › Anbindungen** (Recht `oauth.manage`, nur Administration).
- **Anmeldeschlüssel:** `portal:update` erzeugt sie, wenn sie fehlen (`sso:ensure-keys`). Auf der Seite: Zustand, Fingerabdruck, Erneuern mit Passwort. Der private Schlüssel wird nie angezeigt und gehört in die Sicherung (`storage/oauth-private.key`, `storage/oauth-public.key`).
- **Anbindung anlegen:** Name, Umleitungsadressen (nur `https://`, ohne `#` und Platzhalter, höchstens 10; `http://localhost` nur lokal), erlaubte Rollen (Mehrfachauswahl), vertraulich ja/nein, Zustimmungsseite ja/nein. Client-ID und Geheimnis erscheinen **einmalig**; gespeichert wird nur der Hash.
- **Pflege:** Bearbeiten, Geheimnis erneuern (Passwort), Sperren (widerruft alle Tokens), Löschen (Passwort).
- Jedes Konto trägt eine stabile, nicht erratbare Kennung `users.public_uuid` (wird später als `sub` weitergegeben).

## Teil 2: Anmeldung (OpenID Connect)
- **Discovery:** `/.well-known/openid-configuration`; **Schlüssel:** `/oauth/jwks`; **Anmelden:** `/oauth/authorize`; **Token:** `/oauth/token`; **Benutzerangaben:** `/oauth/userinfo`; **Abmelden:** `/oauth/logout`.
- Ablauf: Authorization Code mit PKCE (S256), RS256-signiertes ID-Token. Kein Password-Grant, kein Implicit.
- **Scopes:** `openid`, `profile` (Name), `email` (Adresse, bestätigt ja/nein), `roles` (Rollenschlüssel), `groups` (Gruppen). Nie dabei: Anschrift, Geburtsdatum, Telefon.
- **Kennung (`sub`):** `users.public_uuid`, nicht die Datenbank-ID.
- **Gruppen:** `vdbs-administration`, `vdbs-vorstand`, `vdbs-geschaeftsstelle`, `vdbs-koordination`, `vdbs-bildungsreferenten`, `vdbs-teamende`, `vdbs-mitglieder`, `vdbs-schulen`.
- **Rollenprüfung:** Vor der Anmeldung prüft das Portal, ob das Konto aktiv ist und eine der erlaubten Rollen der Anbindung hat; sonst Seite „Für diese Anwendung nicht freigegeben“ (403).
- **Zustimmungsseite:** Pro Anbindung abschaltbar (für Systeme des Vereins).
- **Abmeldung:** `/oauth/logout` beendet die Portalsitzung, widerruft alle Tokens des Kontos und leitet nur auf Adressen weiter, deren Herkunft zu einer eingetragenen Umleitungsadresse passt.
- Gesperrte Anbindungen und nicht aktive Konten bekommen von `/oauth/userinfo` ein 401.

## Noch offen (Teil 3)
Zustimmungsseite im Portal-Design, „Verbundene Anwendungen“ im Konto (Widerruf), Einrichtungsanleitung für Nextcloud und Moodle, `passport:purge` im Planer.
