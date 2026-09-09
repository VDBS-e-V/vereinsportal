# Design-QA-Checkliste

Diese Checkliste ergänzt `08_DESIGN_SYSTEM_UND_WEB_UI.md` um die praktische
Abnahme des Designsystems und der damit umgesetzten Fachseiten.

## Ziel

Das Portal soll in aktuellen Versionen von Chrome, Firefox, Edge und Safari
funktionieren und für öffentliche Seiten, Mitgliederbereiche sowie Verwaltung
WCAG 2.2 AA als Zielniveau einhalten.

## 1. Tastatur

- Skip-Link ist beim ersten Tab erreichbar.
- Hauptnavigation, Dropdowns, Account-Menü und Mobile-Menü sind vollständig per Tastatur bedienbar.
- `Escape` schließt geöffnete Overlays und Submenüs, wenn das Muster dies vorsieht.
- Fokus geht nach Dialogschluss zum auslösenden Element zurück.
- Fokusindikatoren sind auf hellen und dunklen Flächen deutlich sichtbar.
- Link-basierte Seitentabs verwenden `aria-current="page"` und keine falschen ARIA-Tab-Rollen.

## 2. Formulare

- Jedes Feld besitzt ein sichtbares Label.
- Pflichtangaben werden nicht nur über Farbe vermittelt.
- Fehlerhafte Felder verwenden `aria-invalid="true"`.
- Fehlermeldungen sind über `aria-describedby` mit dem Feld verbunden.
- Eine Validierungsübersicht ergänzt Feldfehler, ersetzt sie aber nicht.
- Disabled und Readonly bleiben visuell und semantisch unterscheidbar.
- Checkboxen und Radios haben ausreichend große anklickbare Beschriftungen.

## 3. Responsive

Prüfbreiten:

- 320 px
- 375 px
- 768 px
- 1024 px
- 1280 px und größer

Zu prüfen:

- Kein unbeabsichtigter horizontaler Seiten-Scroll.
- Tabellen dürfen gezielt horizontal scrollen.
- Header, Dropdowns und Account-Menü bleiben erreichbar.
- Zweispaltige Formulare werden auf kleinen Breiten einspaltig.
- Lange E-Mail-Adressen, URLs und Dateinamen brechen kontrolliert um.
- Primäre Aktionen bleiben sichtbar und verständlich.

## 4. Bewegung und Kontrast

- `prefers-reduced-motion: reduce` deaktiviert nicht notwendige Animationen und Übergänge.
- `prefers-contrast: more` verstärkt wichtige Konturen.
- Windows Forced Colors / High Contrast erhält erkennbare Grenzen und Current-States.
- Zustände werden nicht ausschließlich über Farbe vermittelt.

## 5. Print

Mindestens prüfen:

- Artikelseite
- Veranstaltungsdetail
- Verwaltungsdetail
- Datentabelle
- Kontakt-/Ressourcenliste

Erwartungen:

- Header, Footer, Breadcrumbs und Aktionen werden nicht mitgedruckt.
- Inhalte nutzen die verfügbare Seitenbreite.
- Tabellenköpfe wiederholen sich auf Folgeseiten.
- Geschlossene Details geben ihren Inhalt aus.
- Datensätze und Tabellenzeilen werden nicht unnötig getrennt.
- Text bleibt auch in Graustufen verständlich.

## 6. Screenreader-Semantik

- Überschriften folgen einer logischen Hierarchie.
- Navigationen haben eindeutige zugängliche Namen.
- Dekorative Icons sind `aria-hidden`.
- Alleinstehende informative Icons besitzen ein Label.
- Notices verwenden nur dort Live-/Alert-Rollen, wo eine dynamische Rückmeldung vorliegt.
- Dialoge besitzen zugängliche Titel und eine nachvollziehbare Fokusreihenfolge.

## 7. Browser-Matrix

Vor einer größeren Designfreigabe mindestens manuell prüfen:

| Bereich | Chrome | Firefox | Edge | Safari |
| --- | --- | --- | --- | --- |
| Header / Dropdowns | offen | offen | offen | offen |
| Formulare | offen | offen | offen | offen |
| Dialoge | offen | offen | offen | offen |
| Tabellen | offen | offen | offen | offen |
| Print | offen | offen | offen | offen |
| Mobile Navigation | offen | offen | offen | offen |

`offen` wird bei der manuellen Abnahme durch Datum oder Ticketreferenz ersetzt.

## 8. Abschlusskriterien

Ein Designblock gilt als abgeschlossen, wenn:

1. die Designreferenz vorhanden ist,
2. wiederverwendbares Styling nicht nur in einer Fachseite steckt,
3. Feature-Tests erfolgreich sind,
4. `npm run build` erfolgreich ist,
5. `git diff --check` keine Whitespace-Fehler meldet,
6. die relevante manuelle QA aus dieser Checkliste durchgeführt wurde.
