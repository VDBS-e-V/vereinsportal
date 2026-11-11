# Paket I: Neue Rollen Mitarbeitende und Geschäftsführung

Aufwand: **2 ET**. Ziel: Zwei neue Systemrollen, die die Pakete F (Anmeldung bei Nextcloud/Moodle) und G (Anträge und Freigabe) brauchen.

## 1 Ausgangslage
Bestehende Rollen (`RoleKey`): `guest`, `member`, `board_member`, `team`, `administration_staff`, `education_coordination`, `coordination`, `administration`, `school`. `RoleSeeder` legt sie an (Test: „seeds exactly the nine known system roles“). Rollenvergabe: Vorstand und Geschäftsstelle dürfen vergeben (außer Administration), Zeitraum und Kommentar sind möglich, jede Vergabe ist protokolliert. Zwei-Faktor ist für Administration, Geschäftsstelle und Vorstand Pflicht (`TwoFactorRequired`).

## 2 Entscheidungen

| Rolle | Bedeutung | Rechte (Entwurf, Q3/Q4 bestätigen) |
|---|---|---|
| **Mitarbeitende** (`employee`) | Hauptamtliche oder Beauftragte, die **auch außerhalb der Geschäftsstelle** arbeiten (z. B. Schulbibliothek). Einsatzort wird im Kommentar der Rollenvergabe geführt (Freitext, bei Q4 als eigenes Feld). | `forms.submit`, Anmeldung bei Nextcloud/Moodle, eigener Bereich „Mitarbeitende“ mit Start, Anträge, Nextcloud-Link. Keine Verwaltung. |
| **Geschäftsführung** (`managing_director`) | Vom **Vorstand benannte Person mit besonderen Rechten**. Nur der Vorstand darf die Rolle vergeben oder entziehen (nicht die Geschäftsstelle, nicht die Administration). | `forms.approve` (Freigabe ab 50 €), `forms.submit`, Nextcloud/Moodle, Kostenstellen pflegen; weitere Rechte nach Q3. **Zwei-Faktor Pflicht.** |
| **Kassenführung** (`treasury`, Zusatzrolle, Übergang, Paket G6) | Vorstandsmitglied für die Kasse, solange die Stelle in der Geschäftsstelle fehlt. | `forms.manage` (Prüfung, Ausnahmen, Export, Kostenstellen). |

Eine Person kann mehrere Rollen tragen. Rollen werden zeitlich begrenzt vergeben können (vorhanden).

## 3 Arbeitspakete

### I1 Rollen und Rechte (0,75 ET)
- `RoleKey`: neue Fälle `Employee`, `ManagingDirector`, `Treasury`. `RoleSeeder` ergänzt sie (idempotent). Migration für die Datenbankzeilen (damit die Livedatenbank sie ohne Seeder-Lauf bekommt; zusätzlich Seeder in der Einrichtung).
- `Capability`: `forms.submit`, `forms.manage`, `forms.approve` (Paket G), `employee.area` (Bereichssicht). `Capabilities::forRole` ergänzen. Matrix-Test erweitern.
- Zwei-Faktor-Pflicht: `managing_director` und `treasury` kommen in die Liste der Pflichtrollen.

### I2 Vergaberegeln (0,5 ET)
- Regel in der Rollenverwaltung: `managing_director` und `treasury` vergibt und entzieht **nur** `board_member` (Vorstand) – analog der bestehenden Regel „Administration vergibt nur die Administration“. Geschäftsstelle sieht die Rolle, kann sie aber nicht ändern.
- `employee` vergeben: Vorstand und Geschäftsstelle.
- Protokoll: Rolle und Zeitraum, kein Kommentarinhalt (Einsatzort wird nicht protokolliert).

### I3 Bereich „Mitarbeitende“ (0,5 ET)
Neuer Bereich in `PortalNavigation` (Gruppe, Kennzeichnung nach `docs/43_BEREICHE.md`): Seiten Start (Hinweise, eigene Anträge, offene Rückfragen), Anträge (Paket G), Link zu Nextcloud (Einstellung `NEXTCLOUD_URL`). Aufgabenübersicht zeigt offene Rückfragen.

### I3a Ausblick (nicht Teil der 2 ET)
Langfristig bekommt der Bereich Mitarbeitende eine **Arbeitszeiterfassung** und weitere Funktionen; siehe `J_AUSBLICK_MITARBEITENDE.md`.

### I4 Tests und Doku (0,25 ET)
`RolesTest`: Rollen vorhanden, Rechte-Matrix, Vergaberegel (Geschäftsstelle kann `managing_director` nicht vergeben, Vorstand kann), Zwei-Faktor-Pflicht, Bereich sichtbar nur mit Rolle. Doku `docs/43_BEREICHE.md` und `docs/40_NUTZERVERWALTUNG.md`.

## 4 Abnahme
1. Vorstand ernennt eine Person zur Geschäftsführung; die Geschäftsstelle kann das nicht.
2. Mitarbeitende sieht den Bereich „Mitarbeitende“ und kann einen Antrag stellen.
3. Geschäftsführung gibt einen Antrag über 50 € frei, aber nicht den eigenen.
4. Beide richten bei der nächsten Anmeldung Zwei-Faktor ein.

## 5 Offene Fragen
Beantwortet: Q3 (Rechte wie geplant), Q4 (Bereich langfristig mit Arbeitszeiterfassung), Q5 (Kassenführung prüft selbst, Freigabe immer durch andere Person).
