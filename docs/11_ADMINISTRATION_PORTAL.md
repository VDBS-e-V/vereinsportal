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
einer aktuell gültigen Zuweisung einer dieser Rollen:

- `administration_staff` – Verwaltung
- `administration` – Administration

Zukünftige oder bereits beendete Rollenzuweisungen geben keinen Zugriff.
Andere Rollen werden nicht automatisch freigeschaltet.

## Aktuelle Routen

```text
/verwaltung
/verwaltung/benutzer
/verwaltung/benutzer/{user}
```

Der Bereich besitzt jetzt zwei Berechtigungsstufen:

- `administration_staff` hat lesenden Zugriff auf Übersicht und Benutzerdaten.
- `administration` darf zusätzlich definierte Verwaltungsaktionen ausführen.

Schreibende Aktionen werden serverseitig nochmals über
`AdministrationAccess::canManage()` geprüft. Die reine Sichtbarkeit eines
Formulars gilt ausdrücklich nicht als Berechtigungsprüfung.

## Benutzerverwaltung

Die Benutzerliste bietet aktuell:

- Suche nach Name oder E-Mail-Adresse
- Statusfilter
- serverseitige Pagination
- Statusdarstellung über das Designsystem
- Link auf eine strukturierte Detailansicht

Die Detailansicht zeigt:

- Kontostatus
- E-Mail-Bestätigung
- letzte Anmeldung
- technische Kontometadaten
- verknüpfte Personendaten
- aktuelle und historische Rollenzuweisungen

## Schreibende Verwaltungsaktionen

Für die Rolle `administration` sind im Benutzerdetail folgende Aktionen
verfügbar:

- aktive Konten deaktivieren
- deaktivierte, bereits bestätigte Konten reaktivieren
- Rollen manuell zuweisen
- aktive manuelle Rollenzuweisungen beenden

Dabei gelten zusätzliche Schutzregeln:

- Das eigene Administrationskonto kann nicht deaktiviert werden.
- Eine aktive eigene `administration`-Rolle kann nicht über die Oberfläche
  beendet werden.
- Automatische und per Konsole erzeugte Rollenzuweisungen können hier nicht
  beendet werden.
- Doppelte aktive oder bereits vorgemerkte Zuweisungen derselben Rolle werden
  verhindert.
- Eine Reaktivierung setzt eine bereits bestätigte E-Mail-Adresse voraus.
- Eine Deaktivierung erhöht `session_version` und entfernt den Remember-Token,
  damit bestehende Anmeldesitzungen invalidiert werden.
- Jede schreibende Aktion verlangt eine Begründung und wird auditierbar
  protokolliert.

Die POST-Endpunkte lauten:

```text
/verwaltung/benutzer/{user}/status
/verwaltung/benutzer/{user}/rollen
/verwaltung/benutzer/{user}/rollen/{assignment}/beenden
```

## Audit

Der Verwaltungsblock ergänzt folgende Ereignisse:

```text
role.manual_assigned
role.manual_ended
account.disabled
account.reactivated
```

Bei einer administrativen Deaktivierung wird zusätzlich das bestehende
Ereignis `auth.sessions.invalidated` mitgeschrieben.

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

Nach diesem Block liegen Benutzerverzeichnis, Kontostatus und manuelle
Rollenverwaltung als erste vollständige Verwaltungsstrecke vor. Als nächste
fachliche Ausbaustufen bieten sich an:

1. Personen- und Mitgliedsdaten anbinden
2. Einladungs- und Freigabeprozesse
3. Kommunikationsvorlagen und Versandstatus
4. Audit-Ansichten für berechtigte Administration
5. feinere fachliche Berechtigungen innerhalb der Verwaltung

Neue schreibende Workflows benötigen weiterhin eine eindeutige
Berechtigungsregel, Audit-Ereignisse und eine fachlich definierte
Bestätigung bzw. Fehlerrückmeldung.
