# Verwaltungsportal

## Ziel

Der Verwaltungsbereich ist die erste reale Fachoberfläche, die auf dem
VDBS-Designsystem aufsetzt. Er befindet sich unter:

```text
http://my.vdb.test/verwaltung
```

Damit bleibt die bestehende lokale Domain- und Session-Konfiguration erhalten.
Es wird für diese Phase keine zusätzliche Subdomain eingeführt.

## Zugriff

Der Bereich ist durch folgende Middleware-Kette geschützt:

```text
web
→ auth
→ identity.revalidate
→ administration.access
```

`administration.access` erlaubt ausschließlich aktive, bestätigte Konten mit
einer aktuell gültigen Zuweisung einer Rolle, der mindestens eine
Verwaltungs-Capability zugeordnet ist. Zukünftige oder bereits beendete
Rollenzuweisungen geben keinen Zugriff.

Die fachliche Autorisierung erfolgt danach nicht mehr über ein globales
Schreibrecht, sondern über konkrete Capabilities. Aktuell sind unter anderem
folgende Fähigkeiten definiert:

```text
persons.read
persons.manage
memberships.read
memberships.manage
membership_documents.read
membership_documents.manage
membership_consents.read
membership_consents.manage
portal_invitations.manage
users.read
users.status.manage
roles.manage
communication.read
communication.manage
audit.read
```

Routen verwenden das Middleware-Gate
`administration.capability:<capability>`. Sicherheitskritische Controller und
Actions prüfen die fachlich benötigte Capability zusätzlich. Die reine
Sichtbarkeit eines Formulars gilt ausdrücklich nicht als
Berechtigungsprüfung.

## Rollen-Presets der aktuellen Beta-Basis

Die Capability-Infrastruktur trennt Rollen und Fachrechte bewusst. Die bereits
vorhandenen Fachmodule sind nach Zuständigkeit aufgeteilt:

### Verwaltung (`administration_staff`)

Die Verwaltung verantwortet im aktuellen System allgemeine Datenverwaltung,
Nutzerkonten sowie Kommunikation:

```text
persons.read
persons.manage
portal_invitations.manage
users.read
users.status.manage
communication.read
communication.manage
```

Mitgliedschaften, Mitgliedschaftsdokumente und mitgliedschaftsbezogene
Zustimmungen gehören nicht zu diesem Preset.

### Vorstand (`board_member`)

Der Vorstand verantwortet die bereits implementierte Mitgliederverwaltung:

```text
memberships.read
memberships.manage
membership_documents.read
membership_documents.manage
membership_consents.read
membership_consents.manage
```

Das Preset erhält bewusst keinen allgemeinen Zugriff auf Personenverzeichnis,
Nutzerkonten, Kommunikation, Rollenverwaltung oder Audit. Dadurch wird vermieden,
dass die Mitgliederzuständigkeit stillschweigend einen allgemeinen
Personendatenzugriff eröffnet.

### Koordination

Für `coordination` und `education_coordination` werden noch keine künstlichen
Verwaltungs-Capabilities vergeben. Die in #37 vorgesehenen Zuständigkeiten für
Mitarbeitende und Freiwillige, Seminare sowie Schulen benötigen zuerst eigene
Fachmodule und eigene Capabilities. Bis dahin öffnet eine reine
Koordinationsrolle den Verwaltungsbereich nicht.

### Volladministration (`administration`)

`administration` bleibt der bewusst separate Vollzugriff und erhält alle
aktuell definierten Capabilities. Insbesondere bleiben die sensiblen Rechte
`roles.manage` und `audit.read` zunächst ausschließlich bei diesem Preset.

Diese Zuordnung bildet die bereits implementierbaren Teile von #37 ab. Neue
Fachmodule werden später mit eigenen Capabilities ergänzt, statt bestehende
breite Rechte dafür zweckzuentfremden.

## Aktuelle Fachbereiche

Der Verwaltungsbereich enthält derzeit:

- Personenverzeichnis und Personendetails
- Mitgliedschaften und Mitgliedschaftsverlauf
- private Mitgliedschaftsdokumente
- historisierte mitgliedschaftsbezogene Zustimmungen
- Portal-Einladungen
- Benutzerverzeichnis, Kontostatus und manuelle Rollenverwaltung
- Kommunikationsvorlagen und Versandhistorie
- Audit-Ansichten

Navigation, Dashboard-Karten und Aktionen werden aus denselben Capabilities
abgeleitet wie die serverseitigen Routen.

## Datenminimierung in Detailansichten

Detailseiten laden fachlich geschützte Daten nur dann, wenn die benötigte
Lesecapability vorhanden ist. Insbesondere werden Dokumente und Zustimmungen
einer Mitgliedschaft nicht allein deshalb geladen, weil ein Konto allgemein
Zugriff auf die Verwaltung besitzt.

Kontostatus und Rollenverwaltung sind ebenfalls getrennt:

- `users.status.manage` steuert Deaktivieren und Reaktivieren von Konten.
- `roles.manage` steuert manuelle Rollenzuweisungen.

Kommunikationsänderungen verwenden `communication.manage`, während lesende
Vorlagen- und Versandansichten über `communication.read` abgesichert sind.

## Lokaler Datenbankstand

Personen- und Mitgliedschaftsdetails greifen auf Fachtabellen zu, die in den
Beta-Blöcken schrittweise ergänzt wurden, darunter `portal_invitations` sowie
die Tabellen für Mitgliedschaftsdokumente und Zustimmungen. Nach einem Pull mit
neuen Migrationen muss daher vor dem Testen der Detailansichten der lokale
Datenbankstand aktualisiert werden:

```text
php artisan migrate
```

Ein veraltetes lokales Schema kann dazu führen, dass Verzeichnisse noch
funktionieren, Detailseiten aber beim Laden neu hinzugekommener Beziehungen mit
einem Datenbankfehler abbrechen.

## Schreibende Verwaltungsaktionen

Schreibende Aktionen verwenden jeweils ihre konkrete Fachcapability. Zusätzlich
gelten weiterhin Schutzregeln wie:

- Das eigene Administrationskonto kann nicht deaktiviert werden.
- Eine aktive eigene `administration`-Rolle kann nicht über die Oberfläche
  beendet werden.
- Automatische und per Konsole erzeugte Rollenzuweisungen können in der
  Benutzerverwaltung nicht als manuelle Zuweisung beendet werden.
- Doppelte aktive oder bereits vorgemerkte Zuweisungen derselben Rolle werden
  verhindert.
- Eine Reaktivierung setzt eine bereits bestätigte E-Mail-Adresse voraus.
- Eine Deaktivierung erhöht `session_version` und entfernt den Remember-Token,
  damit bestehende Anmeldesitzungen invalidiert werden.
- Schreibende Fachaktionen werden, soweit fachlich vorgesehen, auditierbar
  protokolliert.

## Designsystem

Der Verwaltungsbereich verwendet keine eigene Dashboard-Designsprache.
Verwendet werden die bestehenden Muster:

```text
Portal Header
Page Title
Key Facts
Search / Filter
Table
Pagination
Metadata List
Record List
Status
Empty State
Portal Footer
```

Es gibt weiterhin keine permanente globale Sidebar.

## Nächste fachliche Schritte

Für #37 sind die heute vorhandenen Module damit fachlich getrennt. Noch offen
sind eigene Capability-Gruppen und Fachmodule für:

- Finanzen
- Fundraising
- Vereinsrechtliches
- Mitarbeitende und Freiwillige
- Seminare
- Schulen / Schulkoordination

Zusätzlich bleiben in der Communication-Domain insbesondere Vorlagenvorschau
und ein kontrollierter Retry fehlgeschlagener Zustellungen als Beta-Gaps offen.

Neue Workflows benötigen weiterhin eine eindeutige Capability, serverseitige
Absicherung, passende UI-Sichtbarkeit, Audit-Ereignisse bei relevanten
Änderungen sowie eine fachlich definierte Bestätigung bzw. Fehlerrückmeldung.
