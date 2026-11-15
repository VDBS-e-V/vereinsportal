# FAQ: Fragen und Antworten

Die FAQ ist vom Kontaktformular getrennt: eigene Seite, eigene Verwaltung, eigene Daten. Kontaktthemen können nur **auf eine FAQ-Kategorie verweisen**; die FAQ kennt den Kontaktbereich nicht.

## Öffentliche Seite (`/faq`)
Menü „FAQ“ (unter „Über das Portal“) und Knopf „Hilfe“ auf der Startseite. Alle sichtbaren Kategorien mit ihren Fragen, mit Sprungmarken zu den Kategorien und einer Suche über Fragen und Antworten. Am Ende steht „Uns schreiben“ (führt zum Kontaktbereich).

## Pflege im Portal (Geschäftsstelle › Inhalte › FAQ, `/verwaltung/faq`)
Berechtigung `faq.manage` (Administration, Geschäftsstelle). Drei Ebenen, jede mit eigenen Seiten:
1. **Kategorien** (Tabelle `/verwaltung/faq`): Name, Zahl der Fragen, ob sie auf der großen FAQ-Seite stehen, Status. „Neue Kategorie“ und „Einstellungen“ (Bezeichnung, Kurzbeschreibung, Reihenfolge, Sichtbar, „Auf der großen FAQ-Seite zeigen“).
2. **Fragen einer Kategorie** (Tabelle `/verwaltung/faq/<kategorie>/fragen`): Frage, Anfang der Antwort, Reihenfolge, Status.
3. **Eine Frage** (eigene Unterseiten zum Anlegen und Bearbeiten): Frage, Antwort (mit Zeichenzähler), Reihenfolge, Sichtbar; „Frage löschen“.

**Mehrfachauswahl** in beiden Tabellen über die Auswahlkästchen des Designsystems (Kopfkästchen wählt alle): Kategorien sichtbar machen, ausblenden oder löschen (mit allen Fragen); Fragen sichtbar machen, ausblenden, **in eine andere Kategorie verschieben** oder löschen.

Ohne Häkchen bei „Auf der großen FAQ-Seite zeigen“ erscheinen die Fragen einer Kategorie nur dort, wo ein Kontaktthema auf sie verweist. Ausgeblendete Kategorien und ausgeblendete Fragen erscheinen nirgends.

## Antworten formatieren
Leerzeile = neuer Absatz, Zeilen mit `- ` = Liste, `**fett**`, `[Text](/pfad)` für Links im Portal oder `[Text](https://adresse)`. Anderes HTML wird maskiert; Links auf `javascript:` oder fremde `//`-Adressen funktionieren nicht (`App\Modules\Faq\Support\FaqText`).

## Verbindung zum Kontaktbereich
Im Kontaktbereich (Geschäftsstelle › Kontaktformular › Themen) wählen Sie bei einem Thema der Art „Führt zu einer FAQ-Seite“ eine FAQ-Kategorie. Deren Fragen erscheinen auf der Themenseite, darunter auf Wunsch „Uns schreiben“. Wird die Kategorie gelöscht, hat das Thema kein Ziel mehr und ist verborgen (außer „Uns schreiben“ ist eingeschaltet).

## Ausgangsbestand
Die Migrationen legen einen **Entwurf** an: die bisherigen sieben Fragen von „Über das Portal“ (dort steht jetzt ein Hinweis auf `/faq`) und weitere Fragen zu Zugang und Konto, Mitgliedschaft, Schulen und Workshops, Team (Einarbeitung, Vertrag, Abrechnung) sowie Datenschutz und Barrierefreiheit. Die Antworten sind bewusst allgemein gehalten (keine Fristen oder Beträge). **Bitte inhaltlich prüfen und im Portal anpassen.**

## Technik
Modul `app/Modules/Faq`: Modelle `FaqCategory`, `FaqEntry`; Controller `FaqPageController` (öffentlich), `FaqAdminController` (Kategorien), `FaqEntryController` (Fragen); Migrationen `2026_12_29_000001_separate_faq_from_contact.php` (überführt die bisherigen FAQ-Themen in Kategorien und entfernt `contact_faqs`) und `2026_12_29_000002_add_active_to_faq_entries.php` (Fragen einzeln ausblendbar). Tests: `tests/Feature/Faq/FaqTest.php`. Protokoll: Ereignisse `faq.*`.
