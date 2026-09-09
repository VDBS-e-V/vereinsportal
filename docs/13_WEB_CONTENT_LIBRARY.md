# VDBS Web Content Bibliothek

## Ziel

Das Designsystem wird schrittweise zu einer Web Content Bibliothek ausgebaut.
Die Bibliothek ist nicht nur eine visuelle Dokumentation, sondern ein
Arbeitswerkzeug für Entwicklung und Redaktion.

## Kategorien

- Elemente: kleine UI-Bausteine wie Buttons und Icons
- Muster: zusammengesetzte Interaktions- und Inhaltsmuster
- Inhalte: wiederverwendbare redaktionelle Content-Bausteine
- Vorlagen: vollständige Seiten- und Bereichsmuster
- Werkzeuge: Browser, Generatoren und Prüfwerkzeuge

## Lebenszyklus

Jeder Eintrag hat genau einen Status:

- `experimental`: noch nicht produktiv einsetzen
- `beta`: nutzbar, API und Darstellung dürfen sich noch ändern
- `stable`: für produktive Seiten vorgesehen
- `deprecated`: nicht mehr für neue Seiten verwenden

## Registry

Die zentrale Registry liegt in `config/web_content_library.php`.
Sie enthält mindestens:

- eindeutige ID
- Name
- Kategorie
- Status
- Beschreibung
- Quellpfad
- Tags

Die Registry ist die Grundlage für Suche, Filter, Codebeispiele,
Entwicklungswerkzeuge und spätere Qualitätsprüfungen.
