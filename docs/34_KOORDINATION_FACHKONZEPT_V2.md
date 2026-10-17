# Fachkonzept Koordination V2.0 – Grundsatzentscheidungen und Arbeitsstand

Stand: 17.10.2026 · **Fachkonzeption im Aufbau, nicht gesamtfreigegeben.** Dieses Dokument beschreibt beschlossene Grundsätze und ausdrücklich gekennzeichnete Vorschläge. Keine Implementierungsfreigabe.

## 1. Bereichsstruktur – beschlossen
- **Ein gemeinsamer Hauptbereich „Koordination“** mit getrennten, berechtigungsabhängigen Unterbereichen:
  1. **Gebietskoordination**: Seminare in zugewiesenen Gebieten.
  2. **Mitarbeiterkoordination**: Mitarbeitende und Freiwilligendienstleistende.
  3. **Koordination Aus- & Weiterbildung**: Aufnahme, Betreuung und Qualifizierungen der Seminarteamenden.
- Eine Person kann für mehrere Koordinationsarten berechtigt sein.
- **Geschäftsstelle und Geschäftsführung** dürfen alle Unterbereiche der Koordination sehen und sämtliche Funktionen dort verwalten – ohne Begrenzung auf einzelne Gebiete.
- Diese fachliche Vollberechtigung innerhalb der **Koordination** bedeutet nicht automatisch allgemeine Systemadministration oder Zugriff auf fremde Fachbereiche. Berechtigungen bleiben serverseitig, granular und ressourcenbezogen; heutige technische Rollen/Capabilities ggf. anzupassen, nicht stillschweigend gleichzusetzen.

## 2. Gebietskoordination – beschlossen
- Eine Gebietskoordination darf ausschließlich Seminare der ihr zugewiesenen Gebiete verwalten. Pro Person sind mehrere Gebiete zulässig.
- Schulen werden **durch die Geschäftsstelle** verwaltet und einem Gebiet zugeordnet.
- Die Gebietszugehörigkeit eines Seminars leitet sich grundsätzlich aus der zugeordneten Schule ab, nicht aus dem Veranstaltungsort. Abweichungen oder Sonderfälle bei fehlender Schule sind noch nicht beschlossen.
- Gebietskoordinationen arbeiten mit den zentral verwalteten Schuldaten; sie erhalten aus ihrer Gebietsrolle nicht automatisch das Recht, Schulstammdaten oder Gebietszuordnungen zu ändern.
- Geschäftsstelle und Geschäftsführung haben auch gebietsübergreifend vollständige Koordinationsrechte.
- **Langfristiges Ziel:** Geschäftsstelle übernimmt zusätzlich die operative Seminar-Koordination. Dies ersetzt die heutigen Gebietskoordinationen nicht sofort.

## 3. Abgrenzung zum freigegebenen Teamkonzept
- Fachliches Gesamtgrundkonzept des **Teambereichs V2.0 wurde ausdrücklich freigegeben**; vgl. `docs/33_TEAM_FACHKONZEPT_V2.md` auf dem Dokumentationsbranch.
- Teamaufnahme gehört in **Koordination Aus- & Weiterbildung**.
- Gebietskoordination organisiert konkrete Seminare, Besetzungen und Leitungswechsel. Die genaue Zuordnung aller fachlichen Entscheidungskompetenzen, etwa abschließende Berichtsprüfung, ist noch als Aufgabenmodell zu bestätigen.
- Mitarbeiterkoordination betreut Mitarbeitende und Freiwilligendienstleistende; Detailprozesse später.
- Allgemeine Vertrags-/Schulstammdaten sollen nicht mehrfach in Gebietskoordination und Geschäftsstelle unabhängig geführt werden.

## 4. Navigationsentwurf – noch nicht freigegeben
- Übersicht über eigene berechtigte Koordinationsaufgaben.
- Gebietskoordination (z.B. Seminarübersicht, Seminarplanung, Besetzungen, Nachbereitung).
- Mitarbeiterkoordination.
- Aus- & Weiterbildung.
- Schulstammdaten und Gebietszuordnung werden in der **Geschäftsstelle/Verwaltung** gepflegt, nicht als zweite Schulverwaltung in der Gebietskoordination.
- Sichtbarkeit darf keinen Zugriff ersetzen. Das bestehende Portal-Bereichsmodell (`docs/32_PORTAL_AREA_MODEL.md`) trennt globale Bereiche, deren Zugriff und sichtbare lokale Navigation.

## 5. Fachkonzept Seminarverwaltung – nächster Arbeitsblock
**Noch zu entscheiden:** minimaler Datensatz für Anlage eines Seminars, zuständige Schule, Datum und Durchführung, Seminarstatus, Zuständigkeit für Anlage/Freigabe, Umgang mit Schulen außerhalb bestehender Gebiete oder Gebietsänderungen.

**Bereits feststehende Schnittstellen:** Besetzung nur durch ausdrückliche Bestätigung der zuständigen Koordination, allein Koordination bestellt Seminarleitungen, offizieller Bericht und Statistik pro Seminartag, freigegebener Berichtsprüfprozess; siehe Team-Fachkonzept.

## 6. Freigabegrenzen
- **Beschlossen:** Drei Koordinationsarten unter einem Hauptbereich, Geschäftsstelle und Geschäftsführung mit vollständigen Koordinationsrechten, gebietsgebundene Gebietskoordination, mehrere Gebiete je Person, Schulverwaltung und Gebietszuordnung durch Geschäftsstelle, langfristige zentralere Seminarorganisation.
- **Vorschläge/offen:** genaue lokale Navigation, Statusmodell und Seminarschema, Ausnahmen in Gebietszuordnung, konkrete technische Capabilities und Zuordnung zu vorhandenen Rollen, Detailzuständigkeiten.
- Keine technische Implementierung oder Änderung bestehender Schutzmechanismen durch dieses Dokument.
