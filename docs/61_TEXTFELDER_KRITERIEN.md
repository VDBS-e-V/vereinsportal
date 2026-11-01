# Textfelder: Recherche, Erfolgskriterien, Plan

Bild: `docs/design/textfelder-system.png` (Quelltext `textfelder-system.html`). Stand: Entwurf, noch nicht im Code. Paket `forms`, Komponenten `input`, `textarea`, `select`, `password`, `search`, `code`, `field` (gemeinsame Grundlage `control.css`, `field.css`, `input-group.css`).

## Quellen
- Nielsen Norman Group: [Placeholders in Form Fields Are Harmful](https://www.nngroup.com/articles/form-design-placeholders/) (Platzhalter sind kein Label; ohne sichtbares Label lässt sich beim Prüfen und nach Fehlern nicht erkennen, was ein Feld meint). Die oft zitierte Zahl „+33 % Fehler“ konnte ich nicht belegen und verwende sie nicht.
- GOV.UK und verwandte Systeme: [GOV.UK Text input](https://design-system.service.gov.uk/components/text-input), [Queensland](https://www.designsystem.qld.gov.au/components/text-input) (Hinweis einzeilig und dauerhaft sichtbar), [Schottland](https://designsystem.gov.scot/components/text-input), [USWDS](https://designsystem.digital.gov/components/text-input/). Die Einzelregeln von GOV.UK (Breitenklassen, Fehlertext) konnte ich nicht abrufen und müssen vor der Umsetzung dort gegengelesen werden.
- Zustände und Details: [Carbon Text input](https://carbondesignsystem.com/components/text-input/specifications) (Farben je Zustand), [eBay Playbook](https://playbook.ebay.com/design-system/components/text-field) (Hinweis unter dem Feld, Fehler ersetzt den Hinweis, Zähler nur mit Limit, nur lesen wird gesendet, deaktiviert nicht), [SAP Fiori](https://experience.sap.com/fiori-design-android/?p=1208) (nur lesen: kopierbar, graue Fläche), [Siemens iX](https://ix.siemens.io/docs/controls/input), [Productboard](https://design.productboard.com/latest/components/form/text-field/usage-mZa3ag0f), [Aetna](https://design.aetna.com/components/android/text-field.html) (Fehler erst nach Interaktion, Hinweis programmatisch verknüpfen).
- Barrierefreiheit (WCAG 2.2): [Deque: Form Inputs, Labels, Instructions](https://dequeuniversity.com/checklists/web/form-input-labels-instructions), [Queensland: Forms Accessibility](https://www.designsystem.qld.gov.au/components/forms/tabs/accessibility), [Orange: Forms](https://a11y-guidelines.orange.com/en/web/design/forms). Kriterien 1.3.5 (Eingabezweck, `autocomplete`), 1.4.11 (Rand 3 : 1), 3.3.1 (Fehler benennen), 3.3.2 (Beschriftung), 3.3.7 (keine doppelte Eingabe). Mehrere Fundstellen sind Blogs; die Texte der W3C-Understanding-Dokumente sind zu prüfen.

## Ist-Stand (Code)
- `input.blade.php` kann: Typen, Größen s/m/l, Präfix/Suffix, Icons, Pflicht/optional als Text, Hinweis/Fehler/Erfolg, Zähler, Label ausblendbar. Rahmen 1 px `--border-input`, Fokus 2 px + Ring, Fehler/Erfolg 2 px, deaktiviert grau, nur lesen gestrichelt.
- Eckig (Radius 0), 44 px Höhe, Dunkelmodus über Tokens.

## Erfolgskriterien
1. Sichtbares Label über dem Feld, nie nur Platzhalter.
2. Reihenfolge Label · Hinweis · Feld · Meldung · Zähler; Hinweis dauerhaft sichtbar und per `aria-describedby` verknüpft.
3. Label ≥ 4,5 : 1, Hinweis ≥ 4,5 : 1, Rand ≥ 3 : 1 (hell 3,5; dunkel ≥ 4,1 auf Seite, Karte und erhöht).
4. Fehler an der Stelle: Text + Icon + 2-px-Rand, nicht nur Farbe; Text sagt, was zu tun ist; `aria-invalid`; die Fehlerübersicht bleibt zusätzlich.
5. Zustände: Standard, Hover, Fokus, Fehler, Erfolg, Deaktiviert, Nur lesen.
6. Fokus: 2-px-Rand + 3-px-Ring, ≥ 3 : 1 (dunkel weiß).
7. Zielgröße 44 px (S 36, L 52), Abstand ≥ 8 px.
8. Breite nach erwartetem Inhalt (PLZ, Hausnummer, Telefon kurz; Name, Straße lang; Standard volle Breite).
9. Richtiger `type`, `inputmode` und `autocomplete`.
10. Nur lesen: fokussierbar, kopierbar, wird gesendet; deaktiviert: nicht.
11. Zähler nur mit Maximum, kein Countdown-Alarm; Überschreitung als Fehler.
12. Bekannte Angaben vorbelegen (3.3.7).
13. Dunkelmodus mit denselben Rollen und gerechneten Kontrasten.

## Plan
1. **Entscheidungen** (unten) klären.
2. **Tokens:** Feldfläche, Rand, Hover, Fehler-/Erfolgs-/Deaktiviert-/Nur-lesen-Farben als semantische Tokens (hell und beide Dark-Blöcke), Kontrasttest wie bei den Buttons.
3. **CSS:** `control.css`, `field.css`, `input-group.css` auf die Tokens; Zustände nach Entwurf; Breitenklassen.
4. **Komponenten:** Meldung mit Icon, Eigenschaft `width`, Standard-`autocomplete` je Typ, Passwort-Knopf mit Namen, Zähler-Logik, Nur-lesen-Verhalten.
5. **Bibliothek und Tests:** Beispielseite mit allen Zuständen, Varianten, Breiten; Tests für Markup, Verknüpfungen (`aria-*`), Kontraste der Tokens.
6. **Seiten:** Prüfen, dass alle Formulare die Komponenten nutzen (keine rohen `input`-Felder); Sonderdesigns nur mit Ihrer Zustimmung.

## Entscheidungen
1. Label immer **oben** (Empfehlung) oder zusätzlich schwebende Labels (NN/g sieht sie nur als Notlösung)?
2. **Pflicht oder optional kennzeichnen?** Der Entwurf kennzeichnet Pflichtfelder mit „(Pflichtfeld)“; die Komponente kann auch „(optional)“ statt Pflicht.
3. **Fehlerzeitpunkt:** nach dem Verlassen des Feldes und beim Absenden (Empfehlung) oder nur beim Absenden?
4. **Nur lesen:** gestrichelter Rand mit Schloss (Entwurf) oder graue Fläche ohne Schloss?
5. **Feldfläche** im hellen Modus: Weiß (Entwurf) oder leicht grau?
6. **Erfolgszustand** beibehalten (nur bei echter Prüfung, z. B. E-Mail bestätigt) oder streichen?
