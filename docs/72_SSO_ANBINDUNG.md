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

## Teil 3: Konto, Aufräumen, Einrichtung
- **Konto › Verbundene Anwendungen:** Jedes Konto sieht die Systeme, bei denen es angemeldet ist, und kann die Freigabe widerrufen.
- **Zustimmungsseite:** Sie nennt in einfacher Sprache, was das System sehen möchte (Name, E-Mail, Rolle, Gruppen).
- **Aufräumen:** `passport:purge` läuft täglich im Planer und entfernt abgelaufene und widerrufene Tokens.

## Einrichtung

### Vorab im Portal
1. Admins › Anbindungen: Schlüssel prüfen (sonst „Schlüssel erzeugen“).
2. „Neue Anbindung“: Name (z. B. Nextcloud), Umleitungsadresse (siehe unten), erlaubte Rollen, vertraulich ja. Client-ID und Geheimnis notieren (das Geheimnis ist nur einmal sichtbar).
3. Die Adresse der Discovery lautet `https://<Ihr-Portal>/.well-known/openid-configuration`.

### Nextcloud (App „OpenID Connect user backend“, `user_oidc`)
1. App installieren und aktivieren.
2. Anbieter anlegen: Bezeichner „Vereinsportal“, Client-ID und Geheimnis aus dem Portal, Discovery-Adresse wie oben.
3. Umleitungsadresse im Portal: `https://<Ihre-Nextcloud>/apps/user_oidc/code`.
4. Zuordnung: Nutzer-ID = `sub`, Anzeigename = `name`, E-Mail = `email`, Gruppen = `groups`. „Automatisch anlegen“ einschalten.
5. Scopes: `openid profile email roles groups`.
6. Gruppenordner (App „Team folders“): Den Gruppen `vdbs-koordination`, `vdbs-vorstand` usw. ordnen Sie Ordner mit eigenen Rechten zu.

### Moodle (OAuth 2 Service, Typ „OpenID Connect“; ab Moodle 3.11)
Voraussetzung: Portal und Moodle laufen über `https://`, Moodle erreicht das Portal von seinem Server aus (Token-Abruf geschieht serverseitig), die Uhren stimmen.

**Im Portal** (Admins › Anbindungen › Neue Anbindung)
1. Name „Moodle“, Umleitungsadresse `https://<Ihr-Moodle>/admin/oauth2callback.php` (genau so, ohne Leerzeichen und ohne `/` am Ende).
2. Erlaubte Rollen wählen (wer sich in Moodle anmelden darf), vertraulich: ja. Zustimmungsseite: für Vereinssysteme aus.
3. Client-ID und Geheimnis sofort notieren; das Geheimnis wird nur einmal gezeigt.

**In Moodle** (als Administration)
1. Website-Administration › Server › OAuth 2-Services › „OpenID Connect“ wählen (bei älteren Versionen „Benutzerdefiniert“). Name „Vereinsportal“, Client-ID, Geheimnis, Basis-URL des Dienstes = Adresse des Portals (`https://<Ihr-Portal>`; Moodle liest daraus die Discovery). „Auf der Anmeldeseite anzeigen“ einschalten; „E-Mail-Bestätigung verlangen“ nur, wenn bestehende Moodle-Konten mit gleicher E-Mail-Adresse verknüpft werden sollen.
2. Beim Dienst „Benutzerfeld-Zuordnungen“: `preferred_username` → Nutzername, `given_name` → Vorname, `family_name` → Nachname, `email` → E-Mail-Adresse. (Der Nutzername ist die stabile Kennung des Portals; Nutzende sehen ihn nicht.)
3. Website-Administration › Plugins › Authentifizierung: „OAuth 2“ aktivieren; „Kontoerstellung zulassen“ (Selbstregistrierung über den Dienst) muss an sein, damit neue Konten angelegt werden.
4. Scopes `openid profile email` (Moodle fordert sie selbst an). Moodle nutzt nur die Anmeldung; Kurse werden nicht aus Rollen abgeleitet.
5. Testen in einem privaten Fenster: Moodle-Anmeldeseite › „Vereinsportal“ › Anmeldung im Portal › Rückkehr nach Moodle.

### Weitere Systeme (Prüfliste)
Discovery-Adresse, Client-ID und Geheimnis, genau eine eingetragene Umleitungsadresse, Scopes, PKCE (S256) wenn unterstützt.

### Wenn es nicht klappt
- **„Für diese Anwendung nicht freigegeben“:** Das Konto hat keine der erlaubten Rollen (Anbindung bearbeiten) oder ist nicht aktiv.
- **Umleitung abgelehnt:** Die Adresse muss genau mit der eingetragenen übereinstimmen (https, ohne Platzhalter).
- **Anmeldung nach Zeit ungültig:** Die Uhren der Server müssen stimmen (Toleranz 60 Sekunden).
- **Nach „Schlüssel erneuern“** melden sich alle einmal neu an.
