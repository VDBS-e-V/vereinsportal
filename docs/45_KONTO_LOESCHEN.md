# Konto löschen (Offboarding)

Unter **Konto > Konto löschen** (`/konto/loeschen`). Die Löschung ist eine **Anonymisierung**: Datensätze bleiben für Verweise bestehen,
verlieren aber jeden Personenbezug.

## Ablauf

1. **Beantragen:** Passwort und Bestätigungstext „LÖSCHEN“ (Dialog). Das Konto ist sofort **gesperrt** (Status „Löschung angefragt“),
   alle Sitzungen enden, eine E-Mail mit **Abbruch-Link** geht an die Adresse. Im Protokoll: „Kontolöschung beantragt“.
2. **Wartefrist** (`ACCOUNT_DELETION_GRACE_DAYS`, Standard 14 Tage): Der Link (signiert, öffentlich, zweistufig: Seite mit Knopf)
   bricht die Löschung ab; die Verwaltung kann sie auf der Nutzerseite abbrechen.
3. **Anonymisieren:** `php artisan account:purge` (täglich geplant, Scheduler-Cron nötig). Vorher geht eine letzte E-Mail an die alte Adresse.

## Was bei der Anonymisierung passiert

| Daten | Wirkung |
|---|---|
| Rollen | enden sofort |
| Zwei-Faktor, Wiederherstellungscodes, E-Mail-Codes, E-Mail-Änderungen, Reset-Tokens, Sitzungen, Profilbild | werden gelöscht |
| Konto | E-Mail wird `geloescht-<ID>@invalid.example`, zufälliges Passwort, Status „Anonymisiert“, `anonymized_at` und `anonymized_ref` gesetzt |
| Person | Name „Gelöschte Person“, Geburtsdatum 1900-01-01, Adresse, Telefon und E-Mail entfernt |
| Mitgliedschaften (Ereignis `AccountAnonymizing`, Listener im Modul Membership) | laufende enden gestern, offene Anträge werden abgelehnt; Dokumente (Dateien) gelöscht; Name, Geburtsdatum, E-Mail, Notizen und Akteursnamen im Verlauf entfernt; Daten und Status bleiben |
| Protokoll | Namen (Akteur, Betroffene) werden zu „Gelöschtes Konto“ bzw. „Gelöschte Person“, Angaben der betroffenen Einträge entfernt (z. B. alte E-Mail); die Einträge bleiben als Nachweis |

Mit derselben E-Mail-Adresse kann man sich danach neu registrieren. Daten, die der Verein aufbewahren muss (Vereinsunterlagen,
Fristen nach `docs/44_MITGLIEDSCHAFTEN.md`), bleiben ohne Namensbezug erhalten.

## Schutz

- Die **letzte aktive Administration** kann ihr Konto nicht löschen (auch nicht durch den Ablauf der Frist; `account:purge` überspringt sie).
- Nur aktive Konten können die Löschung beantragen; das Beantragen ist auf 5 Versuche pro 10 Minuten begrenzt.
- Neue Ereignisse im Protokoll: Kontolöschung beantragt, abgebrochen, Konto gelöscht (anonymisiert).

Tests: `tests/Feature/Access/AccountDeletionTest.php`. Migration: `php artisan migrate`.
