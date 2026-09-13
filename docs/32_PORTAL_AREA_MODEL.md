# Portal-Bereichsmodell

Dieses Dokument definiert den technischen Vertrag für die langfristigen Bereiche des Vereinsportals. Tracking: Issue #48.

Es trennt drei Dinge, die bisher teilweise direkt in Blade-Layouts zusammengebaut werden:

1. **Bereich** – ein eigenständiger fachlicher Portal-Kontext mit eigener Startseite oder eindeutigem Einstieg.
2. **Zugriff** – ob ein Benutzer diesen Bereich überhaupt verwenden darf.
3. **Sichtbarkeit im Bereichswechsler** – ob ein zugänglicher Bereich in der globalen Bereichsliste angezeigt wird.

Damit gilt ausdrücklich: **Ein Bereich kann existieren und erreichbar sein, ohne im Bereichswechsler sichtbar zu sein.**

## 1. Verbindliche Grundregeln

- `Start` ist ein eigener Portal-Kontext, aber **nicht** als Eintrag im Bereichswechsler sichtbar.
- `Mein Profil` ist ein eigener persönlicher Kontext, aber **nicht** als Eintrag im Bereichswechsler sichtbar.
- Sichtbare Bereiche werden nur angezeigt, wenn der angemeldete Benutzer den dafür erforderlichen Zugriff besitzt.
- Ein ausgeblendeter Bereich darf nicht allein durch seine Unsichtbarkeit ungeschützt sein. Zugriffsschutz bleibt serverseitig erforderlich.
- Bereichssichtbarkeit darf keine Ersatz-RBAC werden. Fachliche Capabilities bleiben die Quelle für Berechtigungen.
- Ein Benutzer mit mehreren zulässigen Bereichen sieht die Vereinigung seiner **sichtbaren und zugänglichen** Bereiche.
- Ein Benutzer ohne Zugriff auf einen internen Bereich sieht dessen Namen nicht als deaktivierten/gesperrten Platzhalter.
- Lokale Navigation innerhalb eines Bereichs ist nicht Teil der globalen Bereichsliste.
- Der aktuelle Bereich darf auch dann korrekt im Seitenkontext/Heading erkennbar sein, wenn er selbst im globalen Switcher unsichtbar ist.

## 2. Heute vorhandene Kontexte

Der aktuelle Stand vor Umsetzung von #48 lässt sich so einordnen:

| Technischer Schlüssel | Bezeichnung | Einstieg | Switcher | Zugriff |
| --- | --- | --- | --- | --- |
| `start` | Start | `my.home` | unsichtbar | angemeldeter Portalnutzer |
| `profile` | Mein Profil | `my.account.profile` | unsichtbar | angemeldeter Portalnutzer |
| `administration` | Verwaltung | `administration.home` | sichtbar | `AdministrationAreaAccess` |
| `board` | Vorstand | `board.home` | sichtbar | `BoardAreaAccess` |
| `coordination` | Koordination | `coordination.home` | sichtbar | `CoordinationAreaAccess` |

Das Designsystem besitzt zusätzlich einen technischen Design-Einstieg. Dieser ist kein fachlicher Produktbereich und wird nicht automatisch Bestandteil der langfristigen Bereichstaxonomie aus #48. Seine heutige Darstellung bleibt bis zu einer bewussten separaten Entscheidung unverändert.

Die weiteren langfristigen Bereiche aus dem Screenshot in Issue #48 werden erst ergänzt, wenn ihre Bezeichnungen und fachlichen Zugriffsvoraussetzungen eindeutig als Text vorliegen. Dieses Dokument erfindet keine Namen aus einem nicht maschinenlesbaren Bild.

## 3. Ziel: zentraler Bereichskatalog

Die Bereichsdefinitionen sollen langfristig nicht getrennt in mehreren Blade-Layouts gepflegt werden. Vorgesehen ist ein zentraler Katalog, aus dem der globale Bereichswechsler abgeleitet wird.

Jeder Eintrag benötigt mindestens:

```text
key
label
home route
visible_in_switcher
access predicate / capability
```

Optional können später ergänzt werden:

```text
icon
sort order
status (active / planned)
help/documentation reference
```

Nicht in den Bereichskatalog gehören:

- einzelne Unterseiten,
- lokale Tabs,
- fachliche Aktionen,
- Rollenbezeichnungen als Ersatz für Capabilities.

## 4. Sichtbarkeitsalgorithmus

Für einen angemeldeten Benutzer wird die globale Bereichsliste nach diesem Prinzip gebildet:

```text
alle Bereichsdefinitionen
    -> nur aktuell verfügbare Bereiche
    -> serverseitige Zugriffsregel prüfen
    -> nur visible_in_switcher = true
    -> definierte Sortierung
    -> Linkliste rendern
```

`Start` und `Mein Profil` scheitern dabei bewusst erst an `visible_in_switcher = false`, nicht an ihrer Existenz als Bereich.

Dadurch können andere Teile des Portals weiterhin dieselbe Bereichsdefinition für Titel, Breadcrumbs oder Rücksprungziele verwenden.

## 5. Zugriff bleibt capability-basiert

Die mit PR #42 eingeführte Trennung bleibt unverändert:

### Verwaltung

Erfordert `AdministrationAreaAccess`.

Dort liegen allgemeine Personen-, Benutzer-, Kommunikations- und zulässige Audit-Funktionen entsprechend den granularen Capabilities.

### Vorstand

Erfordert `BoardAreaAccess`.

Mitgliedschaften, Mitgliedschaftsdokumente und Zustimmungsnachweise bleiben ausschließlich in diesem Bereich und zusätzlich durch ihre granularen Membership-Capabilities geschützt.

### Koordination

Erfordert `CoordinationAreaAccess`.

Neue fachliche Funktionen werden später nur nach tatsächlichem Bedarf mit eigenen Capabilities ergänzt.

Ein zentraler Bereichskatalog darf diese Schutzgrenzen **nicht** lockern. Er entscheidet lediglich, welche erlaubten Einstiege dargestellt werden.

## 6. Mehrfachrollen

Ein Nutzer darf mehrere Bereiche gleichzeitig besitzen.

Beispiele:

- nur `administration` ohne Vorstand: Verwaltung + Koordination sichtbar, Vorstand nicht sichtbar/zugänglich;
- nur `board_member`: Vorstand sichtbar, Verwaltung/Koordination nicht sichtbar/zugänglich;
- `administration` + `board_member`: Verwaltung + Vorstand + Koordination sichtbar;
- normales Mitglied: keine internen Staff-Bereiche im Switcher;
- persönliche Kontexte `Start` und `Mein Profil` bleiben unabhängig davon erreichbar, aber im Bereichswechsler unsichtbar.

Die bestehende Capability-Union über aktive Rollenzuweisungen bleibt die Grundlage.

## 7. Bereich vs. Navigation

Beispiel Verwaltung:

- Bereich: `Verwaltung`
- lokale Navigation: Übersicht, Personen, Benutzer, Kommunikation, Audit – jeweils nur soweit die zugehörigen Capabilities vorhanden sind.

Beispiel Vorstand:

- Bereich: `Vorstand`
- lokale Navigation: Übersicht, Mitgliedschaften und später weitere ausdrücklich freigegebene Vorstandsfachfunktionen.

`Personen`, `Mitgliedschaften`, `Audit` oder `Kontoeinstellungen` sind daher keine eigenen globalen Bereiche nur weil sie eigene Seiten besitzen.

## 8. Persönliche Kontexte

### Start

`Start` bleibt der neutrale Einstieg ins Portal.

Erwartung:

- Route vorhanden,
- als Portal-Home verwendbar,
- nicht im globalen Bereichswechsler aufgelistet,
- Breadcrumbs können darauf zurückführen.

### Mein Profil

`Mein Profil` bleibt persönlicher Kontext des angemeldeten Benutzers.

Erwartung:

- eigener direkter Einstieg,
- nicht im globalen Bereichswechsler aufgelistet,
- darf weiterhin über Account-Menü bzw. passende persönliche Navigation erreichbar sein,
- nicht mit Verwaltungs-Personendaten oder Benutzeradministration vermischen.

## 9. Aufnahme eines neuen sichtbaren Bereichs

Ein neuer langfristiger Bereich wird erst umgesetzt, wenn mindestens feststeht:

1. eindeutiger technischer Schlüssel,
2. sichtbare Bezeichnung,
3. fachlicher Zweck,
4. Home-Route,
5. wer Zugriff erhält,
6. welche Capability diesen Zugriff ausdrückt,
7. ob er im Switcher sichtbar sein soll,
8. welche lokalen Navigationseinträge zunächst existieren,
9. welche Daten ausdrücklich **nicht** in diesen Bereich gehören.

Danach:

- Capability/Authorization implementieren,
- Route serverseitig schützen,
- Bereichskatalog ergänzen,
- Home-Seite bereitstellen,
- Switcher-/Direkt-URL-Tests ergänzen,
- Kombination mit Mehrfachrollen testen,
- Datenschutzgrenzen prüfen.

## 10. Akzeptanztests für #48

Unabhängig von den noch nachzuliefernden Screenshot-Bereichen gelten mindestens folgende Tests:

### Existenz und Sichtbarkeit

- [ ] `Start` ist erreichbar, erscheint aber nicht im Bereichswechsler.
- [ ] `Mein Profil` ist erreichbar, erscheint aber nicht im Bereichswechsler.
- [ ] jeder als sichtbar definierte Bereich erscheint bei vorhandenem Zugriff.
- [ ] derselbe Bereich erscheint ohne Zugriff nicht.
- [ ] keine geplanten/nicht verfügbaren Bereiche werden als tote Links angezeigt.

### Zugriff

- [ ] direkte URL ohne erforderliche Capability bleibt gesperrt.
- [ ] Unsichtbarkeit ersetzt keine serverseitige Autorisierung.
- [ ] Vorstandsdaten bleiben ausschließlich für `board_member`-fähige Nutzer erreichbar.
- [ ] `member` bleibt systemverwaltet und wird durch die Bereichsdarstellung nicht zu einer manuell administrierbaren Rolle.

### Mehrfachrollen

- [ ] Bereichsliste entspricht der Union der erlaubten sichtbaren Bereiche.
- [ ] kein Bereich wird doppelt dargestellt.
- [ ] Wechsel zwischen erlaubten Bereichen erhält korrektes Area-Label/Breadcrumbs.

### Konsistenz

- [ ] öffentliches Portal-Layout, Staff-Layout und Design-/Entwicklungsansicht verwenden langfristig dieselbe Quelle für die Bereichsdefinitionen.
- [ ] Bezeichnungen, URLs und Sortierung werden nicht mehrfach unabhängig in Blade-Dateien gepflegt.

## 11. Migrationsreihenfolge

Für die Umsetzung von #48 ist die risikoarme Reihenfolge:

1. finalen Bereichsnamen aus dem Issue-Screenshot als Text bestätigen,
2. zentralen Bereichskatalog einführen,
3. heutige Verwaltung/Vorstand/Koordination unverändert darüber abbilden,
4. `Start` und `Mein Profil` explizit als unsichtbare Bereiche modellieren,
5. bestehende Layout-Duplikation auf den Katalog umstellen,
6. Tests für Sichtbarkeit und Capability-Grenzen ergänzen,
7. zusätzliche langfristige Bereiche einzeln mit eigener fachlicher Zugriffsklärung aufnehmen.

So wird die bestehende Sicherheitsarchitektur nicht für eine reine Navigationsänderung aufgeweicht.

## 12. Offener Input aus Issue #48

Vor Abschluss der Implementierung fehlt noch die textuelle Liste der zusätzlichen Bereiche aus dem eingebetteten Screenshot sowie – falls nicht bereits aus den Rollen eindeutig – die jeweilige fachliche Zugriffsvoraussetzung.

Bis diese Angaben vorliegen, werden keine Bereichsnamen oder Berechtigungen geraten.
