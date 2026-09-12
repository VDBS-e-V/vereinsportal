# Verwaltungsportal

## Ziel

Der Verwaltungsbereich ist die interne Fachoberfläche des Vereinsportals. Er befindet sich unter:

```text
http://my.vdb.test/verwaltung
```

Damit bleibt die bestehende Domain- und Session-Konfiguration erhalten. Es wird keine zusätzliche Verwaltungs-Subdomain eingeführt.

## Zugriff und Berechtigungsmodell

Der Bereich ist grundsätzlich durch folgende Middleware-Kette geschützt:

```text
web
→ auth
→ identity.revalidate
→ administration.access
→ administration.capability:<fachliche Fähigkeit>
```

`administration.access` ist das Eingangstor. Zugriff erhalten nur aktive, bestätigte Konten mit einer aktuell gültigen Rollenzuweisung, die mindestens eine Verwaltungsfähigkeit bereitstellt. Zukünftige oder bereits beendete Rollenzuweisungen zählen nicht. Andere vorhandene Rollen werden nicht automatisch freigeschaltet.

Die fachliche Autorisierung erfolgt anschließend über typisierte Capabilities aus `AdministrationCapability`. Controller, Routen und sichtbare Aktionen verwenden dieselben fachlichen Fähigkeiten; die Sichtbarkeit einer Schaltfläche ist dabei ausdrücklich kein Ersatz für die serverseitige Prüfung.

## Rollen-Presets der Beta

Die bestehende Rollensemantik bleibt erhalten, wird aber zentral auf Capabilities abgebildet.

`administration_staff` besitzt ausschließlich lesende Verwaltungsfähigkeiten:

```text
persons.read
memberships.read
membership_documents.read
membership_consents.read
users.read
communication.read
```

Damit kann die fachliche Verwaltung Personen, Mitgliedschaften, zugehörige Dokumente und Zustimmungsnachweise, Benutzerkonten sowie Kommunikationsdaten einsehen. Schreibaktionen und das Audit-Protokoll bleiben gesperrt.

`administration` besitzt sämtliche aktuell definierten Verwaltungsfähigkeiten:

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

Andere Rollen wie `member`, `board_member`, `team`, `education_coordination` oder `coordination` erhalten durch diese Abbildung keinen Verwaltungszugang.

## Fachliche Gates

Die Routen verwenden jeweils die kleinste erforderliche Capability:

- Personen anzeigen: `persons.read`
- Personen anlegen oder bearbeiten: `persons.manage`
- Mitgliedschaften anzeigen: `memberships.read`
- Mitgliedschaften anlegen, ändern oder beenden: `memberships.manage`
- Mitgliedschaftsdokumente herunterladen: `membership_documents.read`
- Dokumente hochladen oder ersetzen: `membership_documents.manage`
- Zustimmungsnachweise lesen: `membership_consents.read`
- Zustimmungen erfassen oder widerrufen: `membership_consents.manage`
- Portal-Einladungen starten, erneut senden oder widerrufen: `portal_invitations.manage`
- Benutzer lesen: `users.read`
- Kontostatus ändern: `users.status.manage`
- Rollen zuweisen oder beenden: `roles.manage`
- Kommunikationsdaten lesen: `communication.read`
- Kommunikationsvorlagen ändern, veröffentlichen oder aktivieren/deaktivieren: `communication.manage`
- Audit-Protokoll lesen: `audit.read`

Sicherheitskritische Schreibaktionen prüfen ihre Capability zusätzlich in Controller beziehungsweise Action. Dadurch bleibt die Autorisierung auch bei einer späteren Wiederverwendung außerhalb der aktuellen Route erhalten.

## Benutzerverwaltung

Die Benutzerliste bietet:

- Suche nach Name oder E-Mail-Adresse
- Statusfilter
- serverseitige Pagination
- Statusdarstellung über das Designsystem
- Link auf eine strukturierte Detailansicht

Die Detailansicht zeigt Kontodaten, verknüpfte Personendaten und aktuelle beziehungsweise historische Rollenzuweisungen. Schreibrechte sind getrennt:

- `users.status.manage` erlaubt Deaktivieren und Reaktivieren von Konten.
- `roles.manage` erlaubt manuelle Rollenzuweisungen und das Beenden aktiver manueller Zuweisungen.

Dabei gelten weiterhin die Schutzregeln:

- Das eigene Administrationskonto kann nicht deaktiviert werden.
- Eine aktive eigene `administration`-Rolle kann nicht über die Oberfläche beendet werden.
- Automatische und per Konsole erzeugte Rollenzuweisungen können hier nicht beendet werden.
- Doppelte aktive oder bereits vorgemerkte Zuweisungen derselben Rolle werden verhindert.
- Eine Reaktivierung setzt eine bestätigte E-Mail-Adresse voraus.
- Eine Deaktivierung erhöht `session_version` und entfernt den Remember-Token, damit bestehende Anmeldesitzungen invalidiert werden.
- Schreibende Vorgänge werden nachvollziehbar auditiert.

## Personen und Mitgliedschaften

Personen- und Mitgliedschaftsansichten richten Verknüpfungen und Aktionen ebenfalls an den konkreten Capabilities aus. Dadurch kann eine spätere Fachrolle beispielsweise Mitgliedschaften bearbeiten, ohne automatisch Rollen oder Kommunikationsvorlagen verwalten zu dürfen.

Mitgliedschaftsdokumente liegen privat im Laravel-Storage und werden ausschließlich über eine geschützte Downloadroute ausgeliefert. Dokumentdaten und Zustimmungsnachweise werden im Mitgliedschaftsdetail nur geladen und angezeigt, wenn die jeweilige Lesecapability vorhanden ist. Ersetzen von Dokumenten und Widerrufen von Zustimmungen erhalten die bestehende Historie.

## Kommunikation und Audit

Kommunikationsvorlagen und Versandhistorie sind über `communication.read` lesbar. Entwürfe ändern, Versionen veröffentlichen und Vorlagen aktivieren oder deaktivieren erfordert `communication.manage`.

Das Audit-Protokoll ist bewusst eine eigene Fähigkeit (`audit.read`). `administration_staff` erhält diese Fähigkeit im Beta-Preset nicht; `administration` kann Audit-Liste und Detailansichten öffnen.

## Designsystem

Der Verwaltungsbereich verwendet die bestehenden Muster des VDBS-Designsystems, unter anderem Portal Header, Page Title, Key Facts, Suche/Filter, Tabellen, Pagination, Metadata List, Record List, Status, Empty State und Portal Footer. Es gibt weiterhin keine permanente globale Sidebar.

## Weiterentwicklung

Die Capability-Schicht ist bewusst keine frei konfigurierbare RBAC-Datenbank. Neue Fachrollen können später durch eine zentrale Rollen-zu-Capability-Abbildung ergänzt werden, ohne die einzelnen Controller wieder an Rollennamen zu koppeln.

Der nächste Produkt-Schritt nach diesem Berechtigungsblock ist die vollständige Beta-Abnahme der realen Strecke Person → Mitgliedschaft → Einladung → Konto sowie die Prüfung der noch offenen Punkte aus der funktionalen Beta-Roadmap und der manuellen Design-QA.
