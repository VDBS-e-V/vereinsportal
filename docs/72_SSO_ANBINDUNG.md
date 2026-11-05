# Anbindungen (Paket F, Teil 1)

Das Portal ist die einzige Anmeldung. Nextcloud, Moodle und weitere Systeme melden Nutzende über das Portal an (OpenID Connect, Authorization Code mit PKCE auf Laravel Passport).

## Teil 1: Verwaltung
- **Admins › Anbindungen** (Recht `oauth.manage`, nur Administration).
- **Anmeldeschlüssel:** `portal:update` erzeugt sie, wenn sie fehlen (`sso:ensure-keys`). Auf der Seite: Zustand, Fingerabdruck, Erneuern mit Passwort. Der private Schlüssel wird nie angezeigt und gehört in die Sicherung (`storage/oauth-private.key`, `storage/oauth-public.key`).
- **Anbindung anlegen:** Name, Umleitungsadressen (nur `https://`, ohne `#` und Platzhalter, höchstens 10; `http://localhost` nur lokal), erlaubte Rollen (Mehrfachauswahl), vertraulich ja/nein, Zustimmungsseite ja/nein. Client-ID und Geheimnis erscheinen **einmalig**; gespeichert wird nur der Hash.
- **Pflege:** Bearbeiten, Geheimnis erneuern (Passwort), Sperren (widerruft alle Tokens), Löschen (Passwort).
- Jedes Konto trägt eine stabile, nicht erratbare Kennung `users.public_uuid` (wird später als `sub` weitergegeben).

## Noch offen (Teil 2 und 3)
OIDC-Endpunkte (Discovery, JWKS, ID-Token, Userinfo, Abmeldung), Rollenprüfung bei der Anmeldung, Zustimmungsseite, „Verbundene Anwendungen“ im Konto, Einrichtungsanleitung für Nextcloud und Moodle, `passport:purge` im Planer.
