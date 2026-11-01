# Konzept: Profil und Konto

Stand: Schritte 1 und 2 sind umgesetzt (Variante A der Menüleiste); die Seiten unter „Einstellungen“ (Benachrichtigungen, Darstellung) und Schritt 3 und 4 folgen.

## 0. Umgesetzt (Variante A)

Menü im Bereich Konto: **Profil ▾** (Mein Profil, Profil bearbeiten, Sichtbarkeit) · **Stammdaten** · **Anmeldung ▾** (E-Mail, Passwort, Zwei-Faktor, Sitzungen) · **Mitgliedschaft** · **Daten & Löschung**. Die Übersicht (`account.home`) ist über den Bereichstitel erreichbar. Das Avatar-Menü bietet Mein Profil, Mein Konto und Abmelden.

- Entscheidungen (Standardantworten): Bereich heißt weiter „Konto“; alles ist standardmäßig privat; der Anzeigename ist frei wählbar (ohne Eingabe: Vorname und Initial); Profil für alle Angemeldeten außer Schulkonten (403, kein Menüpunkt); Fotos sind dabei.
- Routen: `account.home`, `account.profile`, `account.profile.edit`, `account.profile.update`, `account.profile.visibility`, `account.profile.visibility.update`, `account.data`, `account.data.update`, `account.roles`, `profile.photo`. Die frühere Stammdaten-Seite unter `/konto/profil` heißt jetzt `account.data` (`/konto/stammdaten`).
- Daten: Tabelle `user_profiles` (Anzeigename, Funktion, Kurzvorstellung, Sichtbarkeit je Angabe, Einwilligungszeitpunkt); das Foto liegt am Konto (`users.avatar_path`).
- Dienste: `Profiles` (Speichern, Sichtbarkeit, `shownTo` als einzige Stelle der Sichtbarkeitsprüfung), `ProfilePhotos` (Upload wird mittig quadratisch zugeschnitten, auf 512 Pixel verkleinert und als JPEG neu geschrieben; Metadaten fallen weg; Ablage privat).
- Profilbild: Der Header-Avatar zeigt immer das eigene Foto. Andere sehen es nur nach der gewählten Sichtbarkeit; die Auslieferung (`profile.photo`) prüft sie bei jedem Abruf (404 sonst). Das Foto hat keinen eigenen Alternativtext; als Avatar gilt der Anzeigename.
- Protokoll: `account.profile_edited`, `account.profile_visibility_changed`, `account.profile_photo_changed` ohne Inhalte. „Profil geändert“ heißt jetzt „Stammdaten geändert“.
- Konto löschen entfernt Profil und Foto.

## 1. Ziel und Begriffe

Alles bleibt im **einen** Bereich „Konto“ (`PortalAreas::Konto`, erreichbar über das Avatar-Menü). Neu ist die klare Trennung der Begriffe:

| Begriff | Bedeutung | Wer sieht es | Wer ändert es |
|---|---|---|---|
| **Konto** | Alles Nichtöffentliche: Stammdaten, Anmeldung, Sicherheit, Einstellungen, Daten und Löschung | nur die Person selbst (und die Geschäftsstelle für Stammdaten in der Verwaltung) | die Person; Name und Geburtsdatum die Geschäftsstelle |
| **Profil** | Wie die Person im Portal und später öffentlich auftritt: Anzeigename, Foto, Kurzvorstellung, freigegebene Kontaktwege | je Angabe einstellbar: nur ich, angemeldete Portalnutzer, öffentlich | die Person |

Faustregel: **Was die Person anderen zeigen will, gehört ins Profil. Was das Portal über die Person weiß oder braucht, gehört ins Konto.**

## 2. Aufbau im Bereich Konto

Der Bereich behält Titel, Startseite („Übersicht“) und Menü. Das Menü hat zwei Gruppen mit Untermenü (die Logik dafür gibt es seit dem Bereichsmenü):

```
Konto  (Bereichstitel = Link zur Übersicht)
├─ Profil ▾                       öffentlich
│   ├─ Mein Profil ansehen        Vorschau, so sehen es andere
│   ├─ Profil bearbeiten          Anzeigename, Foto, Kurztext, Kontaktwege
│   └─ Sichtbarkeit               je Angabe: nur ich / Portal / öffentlich
└─ Konto ▾                        nichtöffentlich
    ├─ Stammdaten                 Name, Geburtsdatum, Anschrift, Telefon
    ├─ Anmeldung & Sicherheit     E-Mail, Passwort, Zwei-Faktor, Sitzungen
    ├─ Mitgliedschaft & Rollen    nur lesen: Mitgliedschaft, Rollen, Bereiche
    ├─ Benachrichtigungen         welche Mails das Portal senden darf
    └─ Daten & Löschung           Auskunft/Export, Konto löschen
```

Die Startseite „Übersicht“ zeigt je einen Block für Profil (Vollständigkeit, Vorschau-Link) und Konto (Hinweise: Zwei-Faktor fehlt, E-Mail unbestätigt, Löschung beantragt).

Avatar-Menü im Header: „Mein Profil“, „Mein Konto“, „Abmelden“ (statt bisher Mein Konto, Sicherheit).

## 3. Was wohin zieht (bestehende Seiten)

| Heute | Neu | Hinweis |
|---|---|---|
| Profil (Stammdaten, Mitgliedschaft, Kontakt und Anschrift) | **Konto › Stammdaten** und **Konto › Mitgliedschaft & Rollen** | Route `account.profile` wird frei für das echte Profil |
| E-Mail, Passwort, Sicherheit, Sitzungen | **Konto › Anmeldung & Sicherheit** (vier Unterseiten, ein Menüpunkt mit Untermenü) | URLs bleiben |
| Konto löschen | **Konto › Daten & Löschung** | URL bleibt |
| Teamprofil (Kompetenzen, Erfahrung für die Teamzusammenstellung) | bleibt im Bereich **Teamende** | nicht öffentlich, nur Koordination; siehe 5 |

Neue Routen: `account.data` (Stammdaten), `account.roles` (Mitgliedschaft & Rollen), `account.notifications`, `account.privacy` (Daten & Löschung bündelt die bestehende Löschseite), `account.profile` (Profil ansehen), `account.profile.edit`, `account.profile.visibility`. Alte Adressen (`/konto/profil`) leiten für eine Übergangszeit auf die neuen Seiten um.

## 4. Das Profil im Detail

**Felder** (Tabelle `user_profiles`, 1:1 zu `users`, getrennt von `people`, damit Stammdaten und Öffentliches nicht vermischt werden):

- Anzeigename (Vorschlag: Vorname plus Initial des Nachnamens; frei änderbar, nie die E-Mail)
- Foto mit Alternativtext und Bildquelle (privat gespeichert, ausgeliefert über eine Route wie bei den Artikelbildern, Größen begrenzt)
- Kurzvorstellung (Editor mit der bestehenden Positivliste, max. 1 500 Zeichen)
- Funktion/Schwerpunkt als Freitext (z. B. „Seminarleitung, Schwerpunkt Schülervertretung“)
- Kontaktwege zur Freigabe: Portal-Nachricht, E-Mail, Telefon (jeweils nur, wenn freigegeben)

**Sichtbarkeit** je Feld und Gesamtprofil in drei Stufen: *nur ich* (Standard), *angemeldete Portalnutzer*, *öffentlich*. Das Foto und jede Kontaktangabe sind unabhängig einstellbar. Ohne Entscheidung bleibt alles privat.

**Datenschutz**
- Veröffentlichung ist eine Einwilligung: Beim Umstellen auf „öffentlich“ gibt es einen Hinweis; Zeitpunkt und Fassung werden wie bei `MembershipConsents` festgehalten. Widerruf = Stufe zurückstellen, wirkt sofort.
- Fotos und Kurztexte werden nicht für Schulkonten angeboten (Rolle Schule hat kein Profil).
- Das Protokoll hält nur fest, **dass** ein Profil geändert oder veröffentlicht wurde, keine Inhalte.
- Konto löschen entfernt das Profil mit Foto (Löschablauf in `docs/45_KONTO_LOESCHEN.md` erweitern).

## 4a. Wo das Profil später auftaucht

1. **Mitgliederbereich/Teamverzeichnis** (Stufe „Portal“): Wer ist im Team, mit Foto und Kurztext.
2. **Seminarteam für Schulen**: Schulen sehen auf ihrer Seminarseite das bestätigte Team mit Profil (nur wenn mindestens „Portal“ freigegeben).
3. **Artikel**: Autorenzeile mit Anzeigename und Link (nur bei „öffentlich“).
4. **Öffentliche Seite „Wir“/Vorstand** (Stufe „öffentlich“).

Jede Verwendung prüft die Sichtbarkeit zentral über eine Klasse `ProfileVisibility` (eine Stelle, ein Test), nicht in den Ansichten.

## 5. Abgrenzung zum Teamprofil

Das Teamprofil (Kompetenzen, Erfahrung, Geschlecht, Leitungserfahrung) dient der Koordination bei der Teamzusammenstellung und bleibt **intern**. Es fließt nie automatisch ins Profil. Optional später: eine Auswahl von Kompetenzen als „Schwerpunkte“ im öffentlichen Profil übernehmen (Opt-in je Eintrag).

## 6. Stammdaten und Zuständigkeit

- Die Person ändert selbst: Telefon, Anschrift, Land.
- Name und Geburtsdatum pflegt die Geschäftsstelle (wie bisher). Neu: Button „Änderung melden“ im Konto erzeugt eine Nachricht an die Geschäftsstelle (ohne Inhalt im Protokoll), statt auf Kontaktseite zu verweisen.
- E-Mail ändert die Person selbst über die bestehende Bestätigung.
- Konten ohne Person (z. B. Schulkonten) sehen nur E-Mail, Anmeldung & Sicherheit und Löschung.

## 7. Umsetzung in Schritten

1. **Struktur** (ohne neue Daten): Menü mit Gruppen Profil/Konto, Seite Stammdaten, Mitgliedschaft & Rollen, Übersicht des Bereichs, Avatar-Menü, Umleitungen, Anpassung der Tests und `docs/43_BEREICHE.md`.
2. **Profil-Grundgerüst**: Tabelle `user_profiles`, Seite Bearbeiten und Vorschau, Foto-Upload, Sichtbarkeit mit Einwilligungsvermerk, Löschablauf.
3. **Verwendung**: `ProfileVisibility`, Seminarteam für Schulen, Teamverzeichnis, Autorenzeile.
4. **Einstellungen**: Benachrichtigungen (welche Mails wir senden dürfen, ohne Pflichtmails wie Sicherheit zu unterdrücken), Datenauskunft als Download.

## 8. Entscheidungen, die wir brauchen

1. **Name des Bereichs**: „Konto“ beibehalten (Empfehlung) oder „Profil & Konto“?
2. **Standard der Sichtbarkeit**: alles privat (Empfehlung) oder Anzeigename/Funktion für Portalnutzer sichtbar?
3. **Anzeigename**: freie Wahl (Empfehlung, mit Hinweis) oder fest aus den Stammdaten?
4. **Wer darf ein Profil haben**: alle Angemeldeten außer Schulen (Empfehlung) oder nur Teamende, Mitglieder und Vorstand?
5. **Fotos**: gleich in Schritt 2 oder zuerst nur Text?
6. **Rechtliches**: Einwilligungstext und Datenschutzerklärung für das Profil müssen vor Schritt 3 mit dem Vorstand abgestimmt werden.
