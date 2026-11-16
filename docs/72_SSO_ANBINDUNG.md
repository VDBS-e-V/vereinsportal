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
- **Scopes:** `openid`, `profile` (Name), `email` (Adresse, bestätigt ja/nein), `roles` (Rollenschlüssel), `groups` (Gruppen), `portal_profile` (Funktion `headline`, Kurzvorstellung `bio` als bereinigtes HTML, Profilbild `picture`). Nie dabei: Anschrift, Geburtsdatum, Telefon.
- **`portal_profile`:** Es gilt die Sichtbarkeit „Angemeldete Portalnutzer“ aus dem Profil der Person (Konto › Mein Profil › Sichtbarkeit). Was die Person nur für sich behält, geht nicht raus. Das Bild lädt das System über `/oauth/photo/{id}` mit signierter, zehn Minuten gültiger Adresse (`picture`). Schulkonten haben kein Profil und liefern nichts.
- **Kennung (`sub`):** `users.public_uuid`, nicht die Datenbank-ID.
- **Gruppen:** `vdbs-administration`, `vdbs-vorstand`, `vdbs-geschaeftsstelle`, `vdbs-koordination`, `vdbs-bildungsreferenten`, `vdbs-teamende`, `vdbs-mitglieder`, `vdbs-schulen`.
- **Rollenprüfung:** Vor der Anmeldung prüft das Portal, ob das Konto aktiv ist und eine der erlaubten Rollen der Anbindung hat; sonst Seite „Für diese Anwendung nicht freigegeben“ (403).
- **Zustimmungsseite:** Pro Anbindung abschaltbar (für Systeme des Vereins).
- **Abmeldung:** `/oauth/logout` beendet die Portalsitzung, widerruft alle Tokens des Kontos und leitet nur auf Adressen weiter, deren Herkunft zu einer eingetragenen Umleitungsadresse passt.
- Gesperrte Anbindungen und nicht aktive Konten bekommen von `/oauth/userinfo` ein 401.

## Anmelde- und Abmeldeseiten des Identity Providers
Die Seiten des Portals als Anmeldestelle folgen einem gemeinsamen Gerüst (`<x-sso.page>`, CSS `resources/css/sso.css`): Band „Single Sign-On“, Logo links, Überschrift mit Linie und Inhalt in der Mitte, rechts eine graue Box.
- **Anmeldeseite:** Kommt die Person von einer Anmeldeanfrage eines angebundenen Systems (`/oauth/authorize`), zeigt sie den Namen des Dienstes als Überschrift und rechts „Beschreibung des Dienstes“ (die Beschreibung der Anbindung). Dazu „Wichtige Hinweise“ (u. a. Warnung vor Phishing mit dem Namen der Portal-Domain). Ohne Dienst steht dort „Anmeldung am Vereinsportal“. Ist `portal.login_image` gesetzt, bleibt die bisherige Darstellung mit Bild.
- **Zwei-Faktor-Seite, Zustimmungsseite und „nicht freigegeben“** nutzen dasselbe Gerüst.
- **Abmeldung durch ein System** (`/oauth/logout`, end_session): Das System hat sich selbst abgemeldet. Das Portal zeigt „Sie haben sich erfolgreich vom Dienst X abgemeldet. Sie sind zusätzlich noch bei folgenden Diensten angemeldet: Vereinsportal, …“ und fragt, ob die Person sich auch dort abmelden will. „Ja, abmelden“ beendet die Portalsitzung, widerruft alle Tokens und zeigt die Fortschrittsseite; „Nein“ führt zurück (zur Rücksprung-Adresse des Systems oder ins Portal).
- **Abmeldung im Portal** („Abmelden“ im Profilmenü): Sind angebundene Systeme mit Abmelde-Adresse angemeldet, zeigt die Fortschrittsseite die Liste; sonst geht es wie bisher zur Anmeldung.
- **Fortschrittsseite (Front-Channel Logout):** Der Browser ruft die Abmelde-Adresse jedes Systems in einem unsichtbaren Rahmen auf (`resources/js/sso-logout.js`, ohne JavaScript über `<noscript>`). Dabei geht die Anmeldung des Systems mit; es meldet die Person ab. Der Haken heißt „aufgerufen“: Ob das System tatsächlich abgemeldet hat, sieht der Browser nicht. Das funktioniert nur, wenn Portal und System auf derselben Hauptdomain liegen (z. B. `portal.vdb.schule` und `moodle.vdb.schule`), weil der Browser sonst die Anmeldung des Systems nicht mitsendet.
- **Abmelde-Adresse je Anbindung** (Admins › Anbindungen › Anbindung bearbeiten): Pflicht ist nur `https://` und dieselbe Domain wie eine Umleitungsadresse. Für Moodle (`auth_oidc`): `https://<Moodle>/auth/oidc/logout.php`. Die Discovery meldet `frontchannel_logout_supported`. Das Portal hängt `iss` (Adresse des Portals) an; eine Sitzungskennung (`sid`) liefert es nicht.
- Migration `2027_01_05_000001_add_frontchannel_logout_to_oauth_client_settings.php`.

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
2. Beim Dienst „Benutzerfeld-Zuordnungen“: `preferred_username` → Nutzername, `given_name` → Vorname, `family_name` → Nachname, `email` → E-Mail-Adresse, `bio` → Beschreibung, `picture` → Profilbild (Moodle lädt das Bild bei der Anmeldung herunter), `headline` → Institution (freiwillig). (Der Nutzername ist die stabile Kennung des Portals; Nutzende sehen ihn nicht.)
3. Website-Administration › Plugins › Authentifizierung: „OAuth 2“ aktivieren; „Kontoerstellung zulassen“ (Selbstregistrierung über den Dienst) muss an sein, damit neue Konten angelegt werden.
4. Scopes `openid profile email portal_profile` (beim Dienst „Scopes in der Anmeldeanfrage“ ergänzen). Moodle nutzt nur die Anmeldung; Kurse werden nicht aus Rollen abgeleitet.
5. Testen in einem privaten Fenster: Moodle-Anmeldeseite › „Vereinsportal“ › Anmeldung im Portal › Rückkehr nach Moodle.

### Weitere Systeme (Prüfliste)
Discovery-Adresse, Client-ID und Geheimnis, genau eine eingetragene Umleitungsadresse, Scopes, PKCE (S256) wenn unterstützt.

### Wenn es nicht klappt
- **„Für diese Anwendung nicht freigegeben“:** Das Konto hat keine der erlaubten Rollen (Anbindung bearbeiten) oder ist nicht aktiv.
- **Umleitung abgelehnt:** Die Adresse muss genau mit der eingetragenen übereinstimmen (https, ohne Platzhalter).
- **Anmeldung nach Zeit ungültig:** Die Uhren der Server müssen stimmen (Toleranz 60 Sekunden).
- **Nach „Schlüssel erneuern“** melden sich alle einmal neu an.
