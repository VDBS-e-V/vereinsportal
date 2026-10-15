# VDBS – Fachliche Gesamtarchitektur

**Status:** Entwurf / Zielbild, nicht implementierungsfreigegeben  
**Stand:** 15.10.2026  
**Geltung:** Fachliche Konzeption; bestehende Repository-Roadmap und Sicherheitsgrenzen bleiben maßgeblich.

## Ziel
Das Vereinsportal bündelt langfristig die wesentlichen Prozesse des VDBS. Die Navigation folgt primär der Vereinsstruktur. Zusätzlich ist die **MethodenMatrix** als eigenständige Anwendung mit eigener Domain geplant.

## Acht langfristige Hauptbereiche des Vereinsportals

1. **Mitglieder:** Mitgliedschaft, offizielle Veröffentlichungen, Veranstaltungen, Diskussionen, freiwilliges Mitgliederverzeichnis. Fachliches Grundkonzept V2.0 freigegeben.
2. **Team:** Ausschließlich Seminarteamende; Seminarbörse, eigene Einsätze, Qualifizierungen und Nachbereitung. Konzept in Bearbeitung.
3. **Mitarbeitende & Freiwilligendienst:** Gemeinsamer eigener Bereich für Mitarbeitende sowie BFD-/FSJ-Leistende. Name und Umfang vorläufig.
4. **Koordination:** Planung und organisatorische Steuerung von Seminaren, Teamenden, Schulen sowie nach Zuständigkeit Mitarbeitenden/Freiwilligen.
5. **Vorstand:** Mitgliedschaftsentscheidungen, formelle Vereinsvorgänge und weitere Vorstandsaufgaben.
6. **Verwaltung:** Personen-, Benutzer-, Kommunikations- und weitere berechtigte Verwaltungsprozesse.
7. **Systemadministration:** Technischer Betrieb, Sicherheits- und Systemfunktionen; kein pauschaler fachlicher Datenzugriff im Zielmodell.
8. **Schulen und Partner:** Schulbezogene Anfragen und Zusammenarbeit; Details offen.

**Persönliche Kontexte:** `Start` und `Mein Profil` sind verfügbar, aber nicht im globalen Bereichswechsler aufgeführt.

## Bereichsübergreifende Prinzipien

- Eine Person und ein Benutzerkonto können mehreren fachlichen Bereichen angehören.
- Bereichssichtbarkeit ersetzt niemals die serverseitige Capability-/Objektberechtigungsprüfung.
- Gemeinsame fachliche Datensätze statt mehrfacher Datenhaltung: Seminare, Veranstaltungen, Personen und Kommunikationsvorgänge werden nur einmal geführt, aber bereichsspezifisch angezeigt.
- Vereinsmitgliedschaft, Seminarteamzugehörigkeit, Beschäftigung und Freiwilligendienst sind voneinander unabhängig.
- Daten und Dokumente nur nach tatsächlichem Bedarf und fachlicher Zuständigkeit zugänglich machen.
- Öffentliche Anträge und Veranstaltungsgast-Anmeldungen erfordern nicht automatisch ein Portalbenutzerkonto.

## Zweite Anwendung: MethodenMatrix

Die MethodenMatrix ist eine gesondert zu entwickelnde Cloud-/Dateianwendung auf eigener Domain für **Methoden, Konzepte, Materialien und Vorlagen**. Sie ist das führende System für diese Dateien. Das Vereinsportal hält Seminarbeziehungen und ggf. Links/Referenzen. Keine Personalakten, personenbezogenen Teilnehmerdaten oder Seminarabschlussberichte in der MethodenMatrix. Anmeldung/SSO und Rechte werden gesondert entschieden.

## Abgleich mit Bestand

Relevante Unterlagen: `docs/32_PORTAL_AREA_MODEL.md`, `docs/11_ADMINISTRATION_PORTAL.md`, `docs/23_DEVELOPMENT_ROADMAP.md`, `docs/24_BETA_ACCEPTANCE.md`, `docs/99_CURRENT_HANDOFF.md`. Die vorhandene Beta und ihre Abnahme-/Betriebsgates werden nicht durch dieses langfristige Zielbild ersetzt.

## Offene Architekturentscheidungen

- Technische und organisatorische Integration der MethodenMatrix (SSO, Identitäten, Rechte, Referenzen).
- Fachkonzept für Mitarbeitende & Freiwilligendienst.
- Details und Rollen innerhalb von Koordination, Schulen & Partnern und Systemadministration.
- Abhängigkeiten und Priorisierung gegenüber Beta-Abnahme, Hosting und Betrieb.
