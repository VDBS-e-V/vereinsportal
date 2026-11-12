# Prüfung des Design-Systems

Drei Werkzeuge, die zusammen absichern, dass Änderungen am Design-System nichts verschlechtern.

## 1. Pest (`vendor\bin\pest`)

- `TokensTest`: keine Farbcodes außerhalb von `tokens.css`, Komponenten nutzen nur Semantik, jede Variable ist definiert,
  die beiden Dark-Mode-Blöcke sind identisch.
- `StartpageComponentsTest`, `ButtonTest`, `FormsTest`, `HeroTest`, `ImageTest`: Markup und Zugänglichkeitsattribute der Komponenten.
- `DesignLibraryTest`: jede Bibliotheksseite rendert.

## 2. Kontrastskripte (reines Python, ohne Abhängigkeiten)

```
python scripts/token_contrast.py     # semantische Tokens, hell und dunkel (74 Paare)
python scripts/button_contrast.py    # alle Button-Kombinationen (850)
python scripts/form_contrast.py      # Formularfelder (54)
```

## 3. Zugänglichkeitsprüfung und Bildschirmfotos der Bibliothek (axe-core + Playwright)

Einmalig `npm install` und `npx playwright install chromium`. Dann bei laufendem Portal (z. B. unter Herd):

```
node scripts/design_audit.mjs --url http://vereinsportal.test --email DU@example.org --password GEHEIM --screens
```

Das Skript meldet sich an, öffnet jede Seite unter `/design/*` in Hell und Dunkel in 390, 820 und 1280 px Breite und prüft sie
gegen WCAG 2.0/2.1/2.2 A und AA sowie Best Practices. Ergebnis: `storage/app/design-audit/report.md` (zusammengefasst nach Seite
und Regel) und `report.json`; mit `--screens` zusätzlich ein Bildschirmfoto je Seite, Breite und Thema als Vergleichsbasis.
Exit-Code 1 bei schwerwiegenden Verstößen (critical, serious). Nützliche Optionen: `--only tabs,toast`, `--ignore regel`.
