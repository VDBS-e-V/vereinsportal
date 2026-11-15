# FAQ: Fragen und Antworten

Die FAQ ist vom Kontaktformular getrennt: eigene Seite, eigene Verwaltung, eigene Daten. Kontaktthemen, die zu keinem Formular führen, **verweisen auf eine FAQ-Kategorie** (sie öffnen `/faq` an dieser Kategorie); die FAQ kennt den Kontaktbereich nicht.

## Öffentliche Seite (`/faq`)
Menü „FAQ“ (unter „Über das Portal“) und Knopf „Hilfe“ auf der Startseite. Alle sichtbaren Kategorien mit ihren Fragen, mit Sprungmarken zu den Kategorien und einer Suche über Fragen und Antworten. Am Ende steht „Uns schreiben“ (führt zum Kontaktbereich).

## Pflege im Portal (Geschäftsstelle › Inhalte › FAQ, `/verwaltung/faq`)
Berechtigung `faq.manage` (Administration, Geschäftsstelle). Drei Ebenen, jede mit eigenen Seiten:
1. **Kategorien** (Tabelle `/verwaltung/faq`): Name, Zahl der Fragen, Status. „Neue Kategorie“ und „Einstellungen“ (Bezeichnung, Kurzbeschreibung, Reihenfolge, Sichtbar).
2. **Fragen einer Kategorie** (Tabelle `/verwaltung/faq/<kategorie>/fragen`): Frage, Anfang der Antwort, Reihenfolge, Status.
3. **Eine Frage** (eigene Unterseiten zum Anlegen und Bearbeiten): Frage, Antwort (mit Zeichenzähler), Reihenfolge, Sichtbar; „Frage löschen“.

**Mehrfachauswahl** in beiden Tabellen über die Auswahlkästchen des Designsystems (Kopfkästchen wählt alle): Kategorien sichtbar machen, ausblenden oder löschen (mit allen Fragen); Fragen sichtbar machen, ausblenden, **in eine andere Kategorie verschieben** oder löschen.

Alle sichtbaren Kategorien stehen auf der FAQ-Seite. Ausgeblendete Kategorien und ausgeblendete Fragen erscheinen nirgends.

## Antworten formatieren
Leerzeile = neuer Absatz, Zeilen mit `- ` = Liste, `**fett**`, `[Text](/pfad)` für Links im Portal oder `[Text](https://adresse)`. Anderes HTML wird maskiert; Links auf `javascript:` oder fremde `//`-Adressen funktionieren nicht (`App\Modules\Faq\Support\FaqText`).

## Verbindung zum Kontaktbereich
Im Kontaktbereich (Geschäftsstelle › Kontaktformular › Themen) wählen Sie bei einem Thema der Art „Führt zur FAQ-Seite“ eine FAQ-Kategorie. Das Thema öffnet dann `/faq` an dieser Kategorie; eine eigene Seite unter `/kontakt` gibt es nicht. Wird die Kategorie gelöscht, ausgeblendet oder hat sie keine sichtbare Frage mehr, ist das Thema verborgen.

## Ausgangsbestand
Die Migrationen legen einen **Entwurf** an: die bisherigen sieben Fragen von „Über das Portal“ (dort steht jetzt ein Hinweis auf `/faq`) und weitere Fragen zu Zugang und Konto, Mitgliedschaft, Schulen und Workshops, Team (Einarbeitung, Vertrag, Abrechnung) sowie Datenschutz und Barrierefreiheit. Die Antworten sind bewusst allgemein gehalten (keine Fristen oder Beträge). **Bitte inhaltlich prüfen und im Portal anpassen.**

## Technik
Modul `app/Modules/Faq`: Modelle `FaqCategory`, `FaqEntry`; Controller `FaqPageController` (öffentlich), `FaqAdminController` (Kategorien), `FaqEntryController` (Fragen); Migrationen `2026_12_29_000001_separate_faq_from_contact.php` (überführt die bisherigen FAQ-Themen in Kategorien und entfernt `contact_faqs`), `2026_12_29_000002_add_active_to_faq_entries.php` (Fragen einzeln ausblendbar) und `2026_12_30_000001_contact_topics_only_form_or_faq.php` (Themen der Art „Link“ werden FAQ-Themen, Spalten `target_url`, `faq_offer_form` und `in_faq_page` entfallen). Tests: `tests/Feature/Faq/FaqTest.php`. Protokoll: Ereignisse `faq.*`.
