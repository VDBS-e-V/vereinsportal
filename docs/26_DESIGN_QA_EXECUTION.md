# Manuelle Design- und Accessibility-QA: Ausführung

Dieses Dokument übersetzt `docs/09_DESIGN_QA_CHECKLIST.md` in eine reproduzierbare Testabfolge. Es ersetzt keine automatisierten Tests und keine fachliche Beta-Abnahme; es beschreibt ausschließlich die manuelle Browser-, Interaktions- und Accessibility-Prüfung aus Issue #16.

## 1. Testvorbereitung

Vor Beginn festhalten:

- Commit oder Branch, der geprüft wird
- Betriebssystem
- Browser und Browser-Version
- Bildschirm-/Viewport-Größe
- verwendete Rolle bzw. Testkonto
- aktivierte Accessibility-Einstellungen

Für Befunde immer die kleinste reproduzierbare Kombination dokumentieren: Seite, Rolle, Browser, Breite, Schritte, Erwartung und tatsächliches Ergebnis.

## 2. Repräsentative Seiten statt Vollinventur

Nicht jede Route muss in jeder Browser-/Breitenkombination erneut vollständig geprüft werden. Pro Layout- und Interaktionsfamilie mindestens eine repräsentative Seite auswählen.

Empfohlene Abdeckung:

- öffentliche/unauthentifizierte Seite bzw. Login
- Registrierung oder Formular mit Validierung
- Konto-/Profilseite
- Seite mit Navigation, Dropdown oder Mobile-Menü
- Verwaltungsliste mit Tabelle, Suche und Filtern
- Verwaltungsdetail mit Formular/Aktionen
- Vorstandsbereich mit Mitgliedschaftsdaten
- Koordinationsbereich
- Seite mit Dialog/Bestätigung
- Seite mit Datei-/Dokumentaktion
- Fehlerseite
- druckbare Detail- oder Datensatzseite

Zusätzliche Seiten nur aufnehmen, wenn sie ein eigenes Muster oder einen bekannten Grenzfall besitzen.

## 3. Responsive-Testlauf

Pflichtbreiten:

- 320 px
- 375 px
- 768 px
- 1024 px
- 1280 px oder größer

Pro Breite mindestens prüfen:

1. Seite neu laden, nicht nur DevTools-Breite live verändern.
2. Header und Hauptnavigation öffnen.
3. Eine lokale Navigation bzw. Tabs benutzen.
4. Formular öffnen und mindestens einen Fehlerzustand erzeugen.
5. Tabelle oder Datensatzliste prüfen.
6. Primär- und Sekundäraktionen auslösen bzw. fokussieren.
7. Lange Inhalte prüfen: Name, E-Mail, URL, Dateiname und Ortsangabe.
8. Auf horizontalen Seiten-Scroll achten; gezielt scrollbare Tabellen sind zulässig.
9. Prüfen, dass keine Aktion außerhalb des Viewports unerreichbar wird.
10. Bei 320/375 px besonders auf überlagerte Menüs, abgeschnittene Dialoge und zu kleine Touch-Ziele achten.

Ein Befund, der nur bei einer Breite auftritt, wird trotzdem als echter Fehler behandelt.

## 4. Vollständiger Tastaturpfad

Mit Maus/Touch nicht eingreifen.

### Einstieg

- Seite neu laden.
- Erster `Tab`: Skip-Link muss erreichbar und sichtbar sein.
- Skip-Link aktivieren: Fokus springt zum Hauptinhalt.

### Navigation

- Alle Header-Links erreichen.
- Dropdowns per Tastatur öffnen und schließen.
- `Escape` schließt geöffnete Menüs, soweit das Muster dies vorsieht.
- Mobile-Menü bei kleiner Breite vollständig per Tastatur bedienen.
- Account-Menü öffnen, durchlaufen und schließen.
- Fokus darf nicht unsichtbar verschwinden.

### Formulare

- Reihenfolge folgt der visuellen/logischen Reihenfolge.
- Labels sind verständlich.
- Checkboxen/Radios sind über Beschriftung aktivierbar.
- Fehlerzustand erzeugen und prüfen, dass Fokus/Fehlermeldung nachvollziehbar bleiben.
- Disabled-Elemente werden nicht fälschlich fokussiert; readonly bleibt erreichbar, wenn Information gelesen werden muss.

### Dialoge

- Dialog mit Tastatur öffnen.
- Startfokus ist sinnvoll.
- Fokus bleibt im modalen Kontext, wenn erforderlich.
- `Escape` schließt den Dialog.
- Nach Schließen kehrt der Fokus zum Auslöser zurück.

### Tabellen und Aktionen

- Links/Buttons in Tabellenzeilen haben eine nachvollziehbare Reihenfolge.
- Horizontales Scrollen einer Tabelle verhindert nicht die Tastaturbedienung.

## 5. Screenreader-Stichprobe

Mindestens eine Desktop-Stichprobe durchführen, z. B. NVDA unter Windows oder VoiceOver unter macOS. Mobile Screenreader können ergänzend getestet werden, sind für diesen Gate nicht zwingend, sofern kein mobilspezifischer Befund vorliegt.

Prüfen:

- Seitentitel ist sinnvoll.
- genau eine klare Hauptüberschrift bzw. nachvollziehbare Überschriftenhierarchie
- Landmarken (`header`, Navigation, `main`, Footer) sind verständlich benannt
- Skip-Link und Navigation werden sinnvoll angekündigt
- Formularfelder besitzen Namen, Pflicht-/Fehlerstatus und zugeordnete Fehlermeldungen
- Buttons haben verständliche Namen ohne rein visuelle Icon-Abhängigkeit
- Status-/Hinweismeldungen werden nur dann live angekündigt, wenn sie dynamisch relevant sind
- Dialogtitel und Dialoginhalt sind verständlich
- Tabellen besitzen nachvollziehbare Überschriften
- dekorative Icons erzeugen keinen störenden Zusatzinhalt

Keine Pixel-/Layoutprüfung mit dem Screenreader vermischen; hier geht es um Struktur, Namen, Zustände und Reihenfolge.

## 6. Reduced Motion und Kontrast

### Reduced Motion

Betriebssystem oder Browser auf reduzierte Bewegung stellen und anschließend:

- Menüs/Dropdowns öffnen
- Loading-/Spinner-Muster prüfen
- Dialoge öffnen/schließen
- Seiten mit Übergängen prüfen

Nicht notwendige Animationen müssen deaktiviert oder stark reduziert sein. Funktionale Zustandswechsel müssen weiterhin erkennbar bleiben.

### Erhöhter Kontrast

`prefers-contrast: more` bzw. die entsprechende Betriebssystemeinstellung aktivieren:

- Fokusrahmen
- Eingabefeldgrenzen
- aktive Navigation
- Buttons
- Warn-/Fehlerzustände

müssen weiterhin eindeutig erkennbar sein.

### Forced Colors / Windows High Contrast

Unter Windows mindestens Header/Navigation, Formulare, Buttons, Statusanzeigen und Dialoge prüfen. Eigene Farben dürfen nicht dazu führen, dass Grenzen oder Zustände verschwinden.

## 7. Browser-Matrix

Pflichtbrowser:

- Chrome
- Firefox
- Edge
- Safari

Die vollständige Interaktionsprüfung muss nicht viermal identisch durchgeführt werden. Vorgehen:

- einen Hauptbrowser für den vollständigen Testlauf verwenden,
- in den drei übrigen Browsern die kritischen Muster gezielt gegenprüfen: Header/Dropdowns, Formulare, Dialoge, Tabellen, Mobile-Navigation und Print.

Safari muss auf einer realen Safari/WebKit-Umgebung geprüft werden; reine Chromium-Simulation gilt nicht als Safari-Abnahme.

## 8. Print-Stichprobe

Mindestens folgende Muster drucken bzw. in der Druckvorschau prüfen:

- Inhalts-/Artikelseite
- Detailseite
- Verwaltungsdetail
- Datentabelle
- Kontakt-/Ressourcenliste

Prüfen:

- Navigation/Aktionsleisten werden nicht unnötig gedruckt
- Inhalt wird nicht abgeschnitten
- Tabellenüberschriften und Seitenumbrüche bleiben verständlich
- URLs/Dateinamen sprengen die Seite nicht
- Graustufen bleiben verständlich
- sensible UI-Aktionen oder rein interaktive Elemente erscheinen nicht als nutzlose Druckinhalte

## 9. Befundklassifikation

### Blocker

- Kernfunktion mit Tastatur/Screenreader nicht erreichbar
- Daten oder Aktionen außerhalb des Viewports unerreichbar
- kritische Information nur farblich erkennbar
- Dialog/Fokus blockiert die Bedienung

### Hoch

- erheblicher WCAG-/Bedienbarkeitsfehler mit Workaround
- zentrale Navigation in einem Pflichtbrowser fehlerhaft
- Formularfehler nicht verständlich zuordenbar

### Mittel

- klarer visueller oder responsiver Defekt ohne Funktionsverlust
- inkonsistente Fokus-/Abstands-/Umbruchdarstellung

### Niedrig

- kosmetische Abweichung ohne funktionale oder semantische Auswirkung

Blocker und hohe Befunde verhindern den Abschluss von #16. Mittlere Befunde werden vor Freigabe bewertet und entweder behoben oder bewusst mit Ticket dokumentiert.

## 10. Nachweisformat

Pro Testsession:

```text
Datum:
Commit:
OS:
Browser/Version:
Viewport:
Rolle:
Accessibility-Einstellungen:

Geprüfte Muster:
Befunde:
Tickets:
Ergebnis: bestanden / nicht bestanden
```

Für die Browser-Matrix in `docs/09_DESIGN_QA_CHECKLIST.md` anschließend `offen` nur dann durch Datum oder Ticketreferenz ersetzen, wenn die jeweilige Stichprobe tatsächlich durchgeführt wurde.

## 11. Abschluss von #16

Issue #16 kann geschlossen werden, wenn:

- Pflichtbreiten geprüft sind,
- der vollständige Tastaturpfad bestanden ist,
- eine Screenreader-Stichprobe dokumentiert ist,
- Reduced Motion, erhöhter Kontrast und Forced Colors geprüft sind,
- Chrome, Firefox, Edge und Safari dokumentiert sind,
- Print geprüft ist,
- keine Blocker/hohen Befunde offen sind,
- verbleibende akzeptierte Befunde eigene Tickets besitzen.
