# Lokales Setup (zweites Gerät)

Voraussetzungen: PHP 8.4 oder neuer (Erweiterungen pdo_sqlite, mbstring, openssl, fileinfo; sodium nur für SSO/Passport),
Composer, Node 20+, Git.

```
git clone https://github.com/VDBS-e-V/vereinsportal.git
cd vereinsportal
git checkout claude/cool-ramanujan-fgmbcu
composer install
composer setup
```

`composer setup` legt `.env` und die SQLite-Datei `database/database.sqlite` an, erzeugt den App-Key,
führt die Migrationen aus und baut die Assets (`npm ci`, `npm run build`). Es startet kein `composer install` mehr
und hat kein Zeitlimit.

## Test-Konto und Server

```
php artisan tinker
```
```
App\Modules\Access\Models\User::factory()->create(['email' => 'test@example.org']);
```
Passwort: `password`. Dann in einem eigenen Terminal `php artisan serve` und `http://127.0.0.1:8000/login` öffnen.
Danach die Design-Bibliothek unter `/design`.

Von einem anderen Gerät im Netzwerk: `php artisan serve --host=0.0.0.0 --port=8000`, dann `http://<IP-des-Rechners>:8000`.
Dafür muss die Windows-Firewall Port 8000 zulassen.

## Typische Probleme

- **`Table 'persons' already exists`:** Die Datenbank im `.env` enthält schon Tabellen (z. B. die MySQL-Datenbank `vdb`
  des alten Portals). Für die lokale Entwicklung SQLite verwenden (`DB_CONNECTION=sqlite`, kein `DB_DATABASE`) oder eine
  leere neue Datenbank anlegen. `php artisan migrate:fresh` löscht alle Tabellen der eingestellten Datenbank.
- **Timeout bei `composer install`:** Das Zeitlimit ist abgeschaltet (`process-timeout: 0`). Ein langsamer erster Lauf
  hängt meist am Download oder an Antivirus-Scans im Ordner `vendor`.
- **Tests:** `phpunit.xml` nutzt MySQL (`vdb_testing`). Dafür muss MySQL laufen und die leere Datenbank `vdb_testing` existieren.
- **Fehler nach `git pull`:** `composer dump-autoload`, `php artisan config:clear`, `php artisan view:clear`, `npm run build`.
