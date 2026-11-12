# Prompt für einen lokalen Coding-Agenten (VDBS Vereinsportal)

Kopiere den Block unten als erste Nachricht in eine neue Claude-Code-Session im Projektordner.

````text
Du arbeitest im Repo VDBS-e-V/vereinsportal (Laravel 13, PHP ^8.4.1, Livewire/Volt, Blade, Tailwind 4, Vite, Pest).
Arbeite eigenständig, in kleinen, getesteten Schritten. Fragen nur, wenn eine Entscheidung wirklich beim Nutzer liegt.

## Branch und Git
- Arbeitsbranch: claude/cool-ramanujan-fgmbcu (Basis: rebuild/from-scratch). Zuerst `git pull`.
- Kein Force-Push, kein Rebase gepushter Commits, keinen PR anlegen, außer der Nutzer verlangt es.
- Pro abgeschlossenem Schritt ein Commit, dann `git push -u origin claude/cool-ramanujan-fgmbcu`.
- Commit-Autor: `git -c user.email="mail@jan-brand.me" -c user.name="Jan Brand" commit ...`
- Commit-Nachricht auf Deutsch, Stil `feat(design): ...`, `fix(access): ...`, mit Trailern am Ende:
  Co-Authored-By: Claude <noreply@anthropic.com>
- Commit-Nachrichten und Code-Kommentare enthalten keine Modellnamen.

## Umgebung (Windows)
- PHP 8.5 (Herd Lite), Ordner C:\xampp\htdocs\vereinsportal. ext-sodium fehlt: Composer ggf. mit
  `--ignore-platform-req=ext-sodium` (nur für Tests nötig; Passport/SSO braucht sodium später).
- Nach neuen Klassen/Blade-Pfaden: `composer dump-autoload` und `php artisan view:clear`.
- Standardbefehle:
  composer dump-autoload
  php artisan view:clear
  npm run build
  vendor\bin\pest
- NIE `php artisan serve` starten (blockiert das Terminal).
- Lokale DB zurücksetzen (nur wenn nötig, nur lokale SQLite): `php artisan migrate:fresh`.
- Test-Nutzer lokal: tinker -> App\Modules\Access\Models\User::factory()->create(['email' => 'test@example.org'])
  (Passwort: password).

## Wahrheitsquellen (zuerst lesen)
1. docs/33_DESIGN_SYSTEM_PAKETE.md (Paketstruktur)
2. VDBS-Web-Styleguide.md und Konzeptdokument (liegen beim Nutzer; Kernregeln unten)
3. app/Modules/DesignLibrary/Support/Registry.php (Liste aller Komponenten) und die Seite /design
4. Bestehende Tests unter tests/Feature (Muster für neue Tests)

## Kernregeln Design
- Nur semantische Tokens (--bg-*, --text-*, --border-*, --focus-ring) aus resources/css/vdbs/tokens.css, nie Rohfarben.
- Schrift Lato (400/700/900), Radius 0 (außer --radius-full), Dark Mode ([data-theme="dark"] + prefers-color-scheme).
- WCAG 2.1/2.2 AA: sichtbarer Fokus, Touch-Targets >= 44px (primär), Information nie nur über Farbe,
  Schrift auf Grün und auf Gruppenfarben ist Ink, genau ein h1 pro Seite.
- Struktur: eine Komponente = eine Blade-Datei (resources/views/components/vdbs/<paket>/<name>.blade.php,
  Aufruf <x-vdbs.<paket>.<name>>) + eine CSS-Datei (resources/css/vdbs/components/<paket>/<name>.css),
  CSS-Klassen BEM `.vdbs-name__teil--variante`, Einbindung über <paket>/index.css.
- Jede neue Komponente: Bibliothekseintrag (Registry + resources/views/design-library/entries/<slug>.blade.php),
  Feature-Test (Blade::render), Eintrag in docs/33_DESIGN_SYSTEM_PAKETE.md.
- Platzhalterbilder: placeholder('hero'), placeholder('teaser', 2) (Konvention in public/images/placeholder/README.md).
- Dateien unter resources/css/vdbs/components/_legacy/ nicht anfassen, nicht einbinden.
- CSS des Nutzers (falls er es bereits geschrieben hat) nie überschreiben; bei Konflikten zuerst `git diff` prüfen.

## Kernregeln Backend
- Module unter app/Modules/<Modul>/ (People, Access, AccessGovernance, Sso, Partners, DesignLibrary).
- Status-Spalten immer string (kein MySQL-ENUM); Geheimnisse nur gehasht oder mit encrypted-Cast;
  public_id (ULID) getrennt von interner id; Soft-/Anonymisierungs-Löschung statt Hard-Delete.
- Pro Modul Pest-Feature-Tests, bevor es als fertig gilt. `vendor\bin\pest` muss vor jedem Push grün sein.

## Aufgaben in dieser Reihenfolge (jeweils fertig bauen, testen, committen, pushen)
A. Prüfen: `git pull`, composer dump-autoload, view:clear, npm run build, vendor\bin\pest. Alles grün machen,
   bevor neue Arbeit beginnt. Fehler minimal beheben.
B. Startseite (Route `/`, öffentlich für Gäste sichtbar, für Angemeldete mit Portalbereichen):
   aus den vorhandenen Komponenten zusammensetzen (siehe Muster unter /design/startseite):
   Hero, Quick-Links, Abschnitt mit Bereichs-Chips (App\Support\PortalAreas), Teaser, Split, Kontaktblock,
   Zum-Seitenanfang. Inhalte als Platzhalter in config/portal.php bzw. einer View, nichts erfinden, was als
   Fakt gelesen werden könnte. Test für Gast und Angemeldeten.
C. Formular-Paket (resources/.../forms): Checkbox, Radio-Gruppe, Passwort-Feld (Anzeigen/Verbergen),
   Code-Eingabe (6 Ziffern, autocomplete="one-time-code", inputmode="numeric"), Fehlerübersicht
   (Zusammenfassung mit Sprungmarken), Formularraster. Jeweils mit Bibliothekseintrag und Test.
D. 2FA im Modul Access (nutzt vorhandene Tabellen two_factor_methods, two_factor_email_challenges,
   two_factor_recovery_codes): TOTP-Einrichtung (QR-Code + Secret als Text), E-Mail-Code-Challenge,
   Recovery-Codes (einmal anzeigen, gehasht speichern), Challenge-Seite nach dem Login, Methodenwahl
   (preferred_two_factor_method), Rate-Limits. Sicherheitsrelevantes ausführlich testen (Brute-Force-Sperre,
   Code-Wiederverwendung, Ablauf, Session-Regeneration).
E. Konto/Profil: Konto-Seite (Tabs Profil, Sicherheit), E-Mail-Änderung (email_change_requests mit Bestätigungsmail),
   Passwort ändern, Avatar-Upload. Mail-Versand per Mailable, in Tests Mail::fake().
F. Daten-Paket: Tabelle mit Suche, Filter, Paginierung, Leerzustand, Beschreibungsliste, Tabs, Aktionsmenü;
   darauf Nutzerverwaltung (Liste/Bearbeiten) mit Rechten über AccessGovernance (Rollen, Role-Assignments).
G. Danach die offenen Module der Baureihenfolge: Onboarding, Consent, Messaging, Membership, Offboarding,
   Compliance-Log (siehe Konzeptdokument), jeweils Migration + Model + Tests + UI nach demselben Muster.

## Qualitätsgrenzen
- Vor jedem Push: vendor\bin\pest grün, npm run build ohne Fehler, `php -l` auf neue PHP-Dateien.
- Keine Secrets committen, .env nicht committen.
- Unsichere Entscheidungen (Rollenrechte der Design-Bibliothek, Texte/Inhalte, rechtliche Seiten) nicht raten:
  Platzhalter setzen und im Abschlussbericht benennen.
- Am Ende jedes Schritts kurz berichten: was gebaut, was getestet, was offen, plus die Befehle zum Prüfen
  (ohne php artisan serve).
````
