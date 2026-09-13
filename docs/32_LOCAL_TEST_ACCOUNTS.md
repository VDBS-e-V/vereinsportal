# Lokale Testkonten

Für Rollen- und Berechtigungstests kann die lokale Entwicklungsumgebung je ein eigenes Konto für jede in `RoleKey` definierte Rolle erzeugen.

## Erstellen oder zurücksetzen

```bash
php artisan vdbs:test-accounts create
```

Der Befehl:

- läuft ausschließlich in `local` und `testing`,
- erzeugt für jede Rolle genau ein verwaltetes Testkonto,
- setzt die Konten aktiv und E-Mail-bestätigt,
- hinterlegt jeweils eine bestätigte TOTP-Methode,
- rotiert bei erneutem Aufruf Passwort und TOTP-Secret,
- erhöht bei bestehenden Testkonten die Session-Version und verwirft transiente Auth-Daten,
- übernimmt keine bereits vorhandenen regulären Konten oder Personen mit kollidierender E-Mail-Adresse.

Die Zugangsdaten werden nicht auf der Konsole ausgegeben. Sie liegen nach erfolgreichem Lauf ausschließlich lokal unter:

```text
storage/app/private/test-accounts.json
```

`storage/app/private` ist bereits vollständig gitignored. Die erzeugte Datei enthält Test-Passwörter und TOTP-Secrets und darf nicht committed oder weitergegeben werden.

## Löschen

```bash
php artisan vdbs:test-accounts delete
```

Gelöscht werden ausschließlich Konten, die über diesen Befehl angelegt und durch dessen Console-Rollenzuweisung markiert wurden. Die lokale Zugangsdaten-Datei wird ebenfalls entfernt.

Das über `VDB_DEV_ADMIN_EMAIL` konfigurierte Entwicklungs-Admin-Konto gehört nicht zu diesen Testkonten und wird bewusst nicht verändert oder gelöscht.

Falls ein Testkonto bereits dauerhafte fachliche Daten erzeugt hat und eine Fremdschlüsselbeziehung die harte Löschung verhindert, bricht der Befehl ab. Er entfernt keine fachliche Historie nur deshalb, um eine Testidentität zu löschen. In diesem Fall die abhängigen lokalen Testdaten gezielt entfernen oder die lokale Entwicklungsdatenbank zurücksetzen.

## Adressschema

Die Konten verwenden ausschließlich die reservierte `.test`-Domain. Das Schema lautet:

```text
test.<rollen-key>@vdbs.test
```

Unterstriche in Rollen-Keys werden in der E-Mail-Adresse durch Bindestriche ersetzt. Die tatsächliche vollständige Liste ergibt sich immer aus `RoleKey::cases()`; neue Rollen werden dadurch automatisch in den nächsten Lauf aufgenommen.
