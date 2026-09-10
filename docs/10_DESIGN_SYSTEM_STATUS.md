# Status des VDBS Designsystems

**Basis:** `main`
**Phase:** Implementierung des Designsystem-Baukastens abgeschlossen, manuelle v1-QA offen

## 1. Zielzustand

Das VDBS Portal verwendet ein gemeinsames visuelles und technisches System für
öffentliche Seiten, Benutzerkonto, Vereinsbereiche und Verwaltung. Neue
Fachseiten sollen nicht mit eigener Gestaltung beginnen.

Die zentrale Regel bleibt:

> CSS definiert das visuelle System. Blade-Komponenten standardisieren
> wiederkehrendes oder komplexes Markup. Fachseiten setzen beides zusammen.

## 2. Abgedeckte Grundlagen

- Brandfarben und semantische UI-Farben
- Typografie
- 4-px-Spacing-Rhythmus
- Inhalts-, Text-, Formular- und Wide-Frames
- eckige Formensprache
- Schatten nur für echte Ebenen und Overlays
- Fokus- und Accessibility-Grundlagen
- responsive Seitenränder
- Print-Grundlage

## 3. Abgedeckte Elemente

- Buttons
- Formulare und Feldzustände
- Hinweise
- Empty States
- Loading / Busy
- Validierungsübersicht
- Bestätigung und Gefahr
- Status und Metadaten
- Disclosure
- Dialoge
- lokale Navigation
- Page Tabs
- Pagination
- Suche und Filter
- Tabellen und Toolbars
- Dateien und Uploads
- zentrale Icons

## 4. Abgedeckte Muster

- Teaser
- Medien und Abbildungen
- Artikel und News
- Veranstaltungen
- Kontakte
- Ressourcen und Linklisten
- Key Facts
- Datensatzlisten

## 5. Abgedeckte Vorlagen

- Verwaltungs-Liste
- Verwaltungs-Detail
- Create/Edit-Formular
- öffentliche Übersichtsseite
- Artikelseite
- Veranstaltungsdetail
- Fehlerseiten

## 6. Bereits migrierte echte Seiten

Identity und Konto:

- Anmelden
- Passwort vergessen
- Passwort zurücksetzen
- Zwei-Faktor-Anmeldung
- Registrierung
- Registrierungsstatus
- Registrierungsabschluss und -fehler
- Mein Portal
- Profil
- E-Mail-Adresse
- Passwort ändern
- Sicherheit / Zwei-Faktor
- Kontolöschung

Diese Seiten sollen nicht mehr auf implizite globale Input- oder Button-Stile
angewiesen sein.

## 7. Informationsarchitektur

Der Designbereich ist aufgeteilt in:

```text
Übersicht
Grundlagen
Layout
Header
Elemente
Muster
Vorlagen
Print
```

`Elemente` enthält atomare bzw. technisch wiederverwendbare UI-Bausteine.
`Muster` enthält zusammengesetzte redaktionelle und fachliche Darstellungen.
`Vorlagen` zeigt vollständige Seitentypen.

Im Benutzerportal werden Kontoseiten unter dem gemeinsamen Bereich `Konto`
zusammengefasst und zusätzlich lokal horizontal navigierbar gemacht.

## 8. Was bewusst nicht Teil des Abschlusses ist

Folgende Punkte werden erst mit echten Fachmodulen ergänzt:

- komplexe Bulk-Aktionen
- konfigurierbare Tabellenspalten
- fachmodulspezifische Dashboards
- spezielle Visualisierungen
- neue Domain-Komponenten ohne realen Anwendungsfall

Damit wird verhindert, dass das Designsystem hypothetische Komponenten sammelt,
die später nicht gebraucht werden.

## 9. QA-Status

Die automatisierte Qualitätsbasis auf `main` ist hergestellt. CI prüft unter
anderem Tests, Web Content Library, Frontend-Build, Whitespace und statische
PHP-Analyse. Diese Checks ersetzen jedoch keine manuelle visuelle und
interaktive Abnahme.

Noch durchzuführen nach `docs/09_DESIGN_QA_CHECKLIST.md`:

1. Responsive-Prüfung bei 320, 375, 768, 1024 und 1280+ px
2. vollständige Tastaturnavigation
3. Screenreader-Stichprobe
4. Forced Colors / Windows High Contrast
5. Reduced Motion und erhöhter Kontrast
6. Print-Stichprobe
7. Browser-Matrix für Chrome, Firefox, Edge und Safari
8. visuelle Prüfung realer Inhalte mit langen Namen, E-Mail-Adressen und URLs

Bis diese Punkte dokumentiert abgeschlossen sind, ist Designsystem v1 noch
nicht als freigegeben bzw. `frozen` zu behandeln.

## 10. Abschlusskriterium

Die verbindliche Abschlussprüfung steht in
`docs/22_DESIGN_SYSTEM_V1_FREEZE.md`. Erst wenn die dort genannten
automatischen Prüfungen grün sind und die relevante manuelle QA ohne offene
Punkte abgeschlossen wurde, gilt der Designsystem-Baukasten als Version
`1.0.0`.

Danach lautet die bevorzugte Arbeitsweise:

```text
Fachanforderung
    ↓
bestehendes Muster auswählen
    ↓
Fachseite zusammensetzen
    ↓
nur bei echter Lücke Designsystem erweitern
```
