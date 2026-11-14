# Kontaktformular: Themenwahl, FAQ-Seiten und Formulare

Der öffentliche Kontaktbereich (`/kontakt`) führt in drei Schritten: **1. Thema wählen**, **2. Angaben zur Person**, **3. Anfrage oder weitere Angaben**. Themen, FAQ-Seiten und Formulare lassen sich im Portal ändern (Geschäftsstelle › Inhalte › Kontaktformular, `/verwaltung/kontakt`, Berechtigung `contact_forms.manage` für Administration und Geschäftsstelle).

## Ablauf für Besucherinnen und Besucher
1. **Themenwahl** (`/kontakt`): Bereiche (z. B. „Zugang und Konto“) und darin die Themen, dazu ein Suchfeld. Die Suche findet Titel, Kurzbeschreibung und Stichwörter; jedes Wort muss vorkommen. Ist das Thema nicht dabei, führt „Schreiben Sie uns direkt“ zum Standardformular.
2. Ein Thema führt, je nach Einstellung, zu
   - **einem Formular** (`/kontakt/formular/<kennung>`),
   - **dem Standard-Kontaktformular**,
   - **einer FAQ-Seite** mit Fragen und Antworten; darunter, wenn eingeschaltet, „Uns schreiben“ (zum gewählten Formular oder zum Standardformular),
   - **einer anderen Seite** (Pfad im Portal wie `/zugang-zum-portal` oder eine Adresse mit `https://`).
3. Das Formular fragt zuerst **Vorname, Nachname und E-Mail-Adresse** (immer), dazu freiwillig oder verpflichtend Telefon und Schule/Organisation, dann Betreff und Nachricht oder **nur die eigenen Felder** (Art „Nur Angaben erfassen“). Zum Schluss die Einwilligung.

Die Angaben gehen **per E-Mail** an den Empfänger des Formulars (Antworten an die absendende Person). **Im Portal wird nichts gespeichert.** Auf Wunsch erhält die absendende Person eine Kopie. Schutz: Bot-Schutz (`docs/51`), höchstens 5 Absendungen je 10 Minuten.

## Pflege im Portal
- **Themen und FAQ:** Bereiche (ganz oben) und Themen darunter, jeweils mit Kurzbeschreibung, Stichwörtern, Reihenfolge und Sichtbarkeit. Zwei Ebenen reichen: Bereich › Thema. Bei einer FAQ-Seite pflegen Sie die Fragen und Antworten gleich im Thema. Mehrfachauswahl: mehrere Themen auf einmal sichtbar machen, ausblenden oder löschen.
- **Formulare:** Name, Einleitung, Art (Nachricht oder nur Angaben), Empfänger (E-Mail und Name), Kopie an die absendende Person, Text nach dem Absenden, Abfrage von Telefon und Schule (nein/freiwillig/Pflicht) und **eigene Felder** (Kurzer Text, Langer Text, E-Mail, Telefon, Auswahl aus einer Liste, Ja/Nein, Datum; je Pflichtfeld oder freiwillig, mit Hinweis). Ein Formular ist das **Standardformular** (nicht löschbar).
- Themen ohne erreichbares Ziel (Formular gelöscht oder ausgeschaltet, FAQ ohne Einträge und ohne Formular) bleiben in der öffentlichen Themenwahl verborgen; in der Verwaltung zeigt ein Hinweis „ohne Ziel“.

## Ausgangsbestand
Die Migration `2026_12_28_000001_create_contact_forms.php` legt die bisherigen Empfänger (Allgemeine Anfrage = Standard, Vorstand, Portal-Support, E-Mail-Adressen aus `config/portal.php` bzw. `CONTACT_*_EMAIL`), ein Formular „Rückruf erbeten“ (nur Angaben) und Beispielthemen mit einer FAQ-Seite an. Alles lässt sich im Portal ändern oder löschen.

## Technik
Modul `app/Modules/ContactForms`: Modelle `ContactForm`, `ContactTopic`, `ContactFaq`; `Support\ContactFormFields` (Feldarten, Aufbereitung, Prüfregeln); Controller `ContactFlowController` (öffentlich), `ContactTopicAdminController`, `ContactFormAdminController`; E-Mail `Mail\ContactFormMail`. Tests: `tests/Feature/ContactForms/ContactFormsTest.php`. Protokoll: Ereignisse `contact_forms.*` (nur Namen, keine Eingaben).

## Datenschutz
Es werden keine Eingaben gespeichert; die Daten liegen nur in der E-Mail beim Empfänger. In der Datenschutzerklärung (Kontaktformular) bleibt der Hinweis auf die Einwilligung. Bei Formularen, die Telefon oder Schule abfragen, genügt der Verweis „zum Zweck der Kontaktaufnahme“; bei eigenen Feldern mit besonders schutzbedürftigen Angaben vorher mit der Datenschutzbeauftragten abstimmen.
