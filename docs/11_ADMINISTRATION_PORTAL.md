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

Die Capability-Infrastruktur trennt Rollen und Fachrechte bewusst. Der
aktuelle Beta-Stand bildet zunächst die bisherige Semantik ab:

- `administration_staff` erhält lesende Fähigkeiten für Personen,
  Mitgliedschaften, Dokumente, Zustimmungen, Benutzer und Kommunikation.
- `administration` erhält zusätzlich die aktuell vorhandenen schreibenden
  Verwaltungsfähigkeiten und Audit-Lesezugriff.
- Andere Rollen erhalten durch diesen Basisschritt noch keinen zusätzlichen
  Verwaltungszugang.

Diese Presets sind eine Übergangsbasis. Die fachliche Trennung von Verwaltung,
Vorstand und Koordination wird separat in #37 konkretisiert. Weil Controller,
Routen und UI bereits auf Capabilities statt Rollennamen prüfen, kann diese
Zuordnung angepasst werden, ohne die Fachendpunkte erneut umzubauen.

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

Die Capability-Basis ist die Voraussetzung für die nächsten organisatorischen
Ausbaustufen. Als Nächstes werden die Zuständigkeiten aus #37 auf die bereits
vorhandenen und künftigen Fachmodule abgebildet. Zusätzlich bleiben in der
Communication-Domain insbesondere Vorlagenvorschau und ein kontrollierter Retry
fehlgeschlagener Zustellungen als Beta-Gaps offen.

Neue Workflows benötigen weiterhin eine eindeutige Capability, serverseitige
Absicherung, passende UI-Sichtbarkeit, Audit-Ereignisse bei relevanten
Änderungen sowie eine fachlich definierte Bestätigung bzw. Fehlerrückmeldung.
