# Accessibility & Interaction Polish

Der finale Accessibility-Pass ergänzt keine neue Fachfunktion.

## Festgelegte Regeln

- Formularfelder behalten zusätzlich zum semantischen Feldzustand einen
  deutlich sichtbaren `:focus-visible`-Ring.
- Checkboxen und Radios erhalten einen expliziten Tastaturfokus.
- Disclosure-Summaries erfüllen auf Touch-Geräten mindestens 44 px Zielhöhe.
- Dialogaktionen werden auf kleinen Viewports zu eindeutigen Vollbreiten-Aktionen.
- Datei-Inputs erhalten dieselbe Fokusqualität wie andere Controls.
- `hidden` bleibt auch bei komponentenspezifischen Display-Regeln verlässlich
  ausgeblendet.
- Lange Werte dürfen bei 200-%-Zoom und kleinen Viewports umbrechen.
