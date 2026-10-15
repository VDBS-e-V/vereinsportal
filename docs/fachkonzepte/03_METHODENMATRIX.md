# MethodenMatrix – Produktsteckbrief

**Status:** Früher Entwurf, eigenständiges Fachkonzept ausstehend  
**Stand:** 15.10.2026

## Zweck
Die **MethodenMatrix** ist Teil des VDBS-Gesamtprojekts, wird aber als **eigene Anwendung unter eigener Domain** entwickelt. Sie ist eine Cloud-/Dateiplattform **ausschließlich für Materialien und Vorlagen**, beispielsweise Seminarmethoden, Konzepte, Lehrmaterialien, Dokumentvorlagen und dazugehörige Dateien.

## Systemgrenzen

**MethodenMatrix ist führend für:** Material- und Vorlagendateien, Versionierung, Sammlungen, ggf. Metadaten, Suche und Freigaben.

**Vereinsportal ist führend für:** Teamaufnahme, Nutzer-/Personenbeziehungen, Qualifikationsdaten, Seminare, Schuleinsatz-Zuordnungen, Teamrollen, Teilnehmerstatistiken, Seminarberichte sowie persönliche Nachweise.

Keine allgemeine Ablage von Personalakten, personenbezogenen Schüler-/Teilnehmerdaten, Schulfallakten oder Abschlussberichten in der MethodenMatrix. Links/Referenzen auf Materialien können im Portal angezeigt werden.

## Bekannter Nutzungsfall
Ein Seminarteamender öffnet im Vereinsportal sein Seminar, ruft zugewiesene Methoden und Vorlagen über die MethodenMatrix auf und verwendet sie bei der Vorbereitung. Das Portal soll keine zweite vollständige Materialablage erzeugen.

## Offene Anforderungen für eigenes Fachkonzept

- Eigene Domain und Betriebs-/Hostingstrategie.
- Anmeldung: SSO mit Vereinsportal, separates Login oder später festzulegen.
- Rollen und Rechte: Lesen, Upload, Bearbeiten, Freigeben, Entfernen, Materialverantwortung.
- Allgemeine Bibliothek vs. freigabegeschützte sammlungs- oder seminarbezogene Materialien.
- Such-, Tagging-, Ordnungs- und Versionsmodell.
- Dateitypen, Dateigrößen, Dateisicherheit, Virenprüfung, Vorschau, Rechte an Materialien.
- Löschung, Archivierung, Lizenzfragen und Nachvollziehbarkeit von Änderungen.
- Schnittstellen und Ausfallszenarien zwischen den Anwendungen.

**Nicht fachlich freigegeben. Keine vorhandene Domain oder SSO-Implementierung unterstellen.**
