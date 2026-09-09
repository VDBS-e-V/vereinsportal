<?php

return [
    'version' => '1.0.0',

    'frozen' => true,

    'freeze_policy' => 'Neue Bausteine entstehen nur aus realen Produkt- oder Content-Anforderungen und werden anschließend in die Bibliothek übernommen.',

    'statuses' => [
        'experimental' => [
            'label' => 'Experimentell',
            'description' => 'Noch nicht für produktive Seiten vorgesehen.',
        ],
        'beta' => [
            'label' => 'Beta',
            'description' => 'Nutzbar, Änderungen an API und Darstellung sind noch möglich.',
        ],
        'stable' => [
            'label' => 'Stabil',
            'description' => 'Für produktive Seiten vorgesehen.',
        ],
        'deprecated' => [
            'label' => 'Veraltet',
            'description' => 'Nicht mehr für neue Seiten verwenden.',
        ],
    ],

    'categories' => [
        'foundations' => 'Grundlagen',
        'elements' => 'Elemente',
        'patterns' => 'Muster',
        'content' => 'Inhalte',
        'templates' => 'Vorlagen',
        'tools' => 'Werkzeuge',
    ],

    /*
     * Vollständiges Inventar der aktuell bewusst gepflegten VDBS-
     * Bibliotheksbausteine. Neue Einträge müssen mindestens die hier
     * verwendeten Kernfelder besitzen. `vdbs:library-check` prüft zudem,
     * ob neue Blade-Komponenten oder Komponenten-CSS-Dateien unregistriert
     * geblieben sind.
     */
    'items' => [
        [
            'id' => 'design-tokens',
            'name' => 'Design Tokens',
            'category' => 'foundations',
            'status' => 'stable',
            'description' => 'Farben, Typografie, Abstände, Größen, Layer und Bewegungswerte als gemeinsame Basis.',
            'source' => 'resources/css/vdbs/tokens.css',
            'files' => [
                'resources/css/vdbs/tokens.css',
            ],
            'tags' => [
                'tokens',
                'farben',
                'typografie',
                'spacing',
            ],
            'usage' => [
                'Neue Komponenten ausschließlich auf Basis der vorhandenen Tokens aufbauen.',
            ],
            'avoid' => [
                'Neue Farb-, Radius- oder Spacing-Werte lokal erfinden.',
            ],
            'accessibility' => [
                'Semantische Farben nie als einziges Unterscheidungsmerkmal verwenden.',
            ],
        ],
        [
            'id' => 'base-typography',
            'name' => 'Basis & Typografie',
            'category' => 'foundations',
            'status' => 'stable',
            'description' => 'Globale HTML-Grundregeln, Schriftfamilien und typografische Basiseinstellungen.',
            'source' => 'resources/css/vdbs/base.css',
            'files' => [
                'resources/css/vdbs/base.css',
            ],
            'tags' => [
                'basis',
                'schrift',
                'text',
            ],
            'usage' => [
                'Für globale Grunddarstellung und Textverhalten.',
            ],
            'avoid' => [
                'Komponentenspezifische Regeln in die globale Basis verschieben.',
            ],
            'accessibility' => [
                'Lesbare Zeilenhöhen und ausreichende Kontraste beibehalten.',
            ],
        ],
        [
            'id' => 'layout-system',
            'name' => 'Layout & Raster',
            'category' => 'foundations',
            'status' => 'stable',
            'description' => 'Seitenbreiten, Frames, Grid-Grundlagen und horizontale Seitenraster.',
            'source' => 'resources/css/vdbs/layout.css',
            'files' => [
                'resources/css/vdbs/layout.css',
                'resources/css/vdbs/frames.css',
                'resources/css/vdbs/components/grids.css',
                'resources/views/components/vdbs/frame.blade.php',
            ],
            'tags' => [
                'layout',
                'grid',
                'frame',
                'responsive',
            ],
            'usage' => [
                'Seiten und größere Inhaltsbereiche auf den gemeinsamen Frames aufbauen.',
            ],
            'avoid' => [
                'Neue maximale Inhaltsbreiten pro Seite definieren.',
            ],
            'accessibility' => [
                'Inhalte müssen bei Zoom und kleinen Viewports ohne horizontales Seiten-Scrolling funktionieren.',
            ],
        ],
        [
            'id' => 'accessibility',
            'name' => 'Barrierefreiheit',
            'category' => 'foundations',
            'status' => 'stable',
            'description' => 'Skip-Link, Fokusdarstellung und globale Accessibility-Hilfen.',
            'source' => 'resources/css/vdbs/a11y.css',
            'files' => [
                'resources/css/vdbs/a11y.css',
            ],
            'tags' => [
                'a11y',
                'fokus',
                'tastatur',
                'wcag',
            ],
            'usage' => [
                'Bei jedem neuen interaktiven Muster gegen diese Basis prüfen.',
            ],
            'avoid' => [
                'Fokusindikatoren entfernen oder nur Hover-Zustände gestalten.',
            ],
            'accessibility' => [
                'WCAG 2.2 AA bleibt Zielniveau des Systems.',
            ],
        ],
        [
            'id' => 'print-system',
            'name' => 'Print',
            'category' => 'foundations',
            'status' => 'stable',
            'description' => 'Druckregeln für reduzierte, lesbare und informationsorientierte Ausgaben.',
            'source' => 'resources/css/vdbs/print.css',
            'files' => [
                'resources/css/vdbs/print.css',
            ],
            'tags' => [
                'print',
                'druck',
                'pdf',
            ],
            'usage' => [
                'Für Seiten, die sinnvoll ausgedruckt oder als PDF gespeichert werden.',
            ],
            'avoid' => [
                'Interaktive Elemente ungefiltert in Druckansichten übernehmen.',
            ],
            'accessibility' => [
                'Druckinhalte müssen auch ohne Farbe verständlich bleiben.',
            ],
        ],
        [
            'id' => 'button',
            'name' => 'Buttons',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Primäre, sekundäre, ruhige, Akzent- und destruktive Aktionen mit Größenvarianten.',
            'source' => 'resources/css/vdbs/components/buttons.css',
            'files' => [
                'resources/css/vdbs/components/buttons.css',
            ],
            'tags' => [
                'button',
                'aktion',
                'cta',
                'formular',
            ],
            'usage' => [
                'Für echte Aktionen und klar priorisierte Handlungsoptionen.',
            ],
            'avoid' => [
                'Links als Buttons gestalten, wenn keine Aktion ausgelöst wird; mehrere Primäraktionen nebeneinander.',
            ],
            'accessibility' => [
                'Beschriftungen müssen die ausgelöste Aktion eindeutig benennen.',
            ],
        ],
        [
            'id' => 'icon',
            'name' => 'Icons',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Zentrale SVG-Icons mit dekorativer und beschrifteter Verwendung.',
            'source' => 'resources/views/components/vdbs/icon.blade.php',
            'files' => [
                'resources/views/components/vdbs/icon.blade.php',
                'resources/css/vdbs/components/icons.css',
            ],
            'tags' => [
                'icon',
                'svg',
                'symbol',
            ],
            'usage' => [
                'Vorhandene Icons wiederverwenden und neue Symbole zentral als @case ergänzen.',
            ],
            'avoid' => [
                'Inline-SVGs über Fachseiten verteilen oder dieselbe Bedeutung mit wechselnden Symbolen darstellen.',
            ],
            'accessibility' => [
                'Dekorative Icons bleiben aria-hidden; alleinstehende informative Icons benötigen ein Label.',
            ],
        ],
        [
            'id' => 'badge',
            'name' => 'Badge',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Kurze, kompakte Kennzeichnung für Kategorien oder zusätzliche Eigenschaften.',
            'source' => 'resources/views/components/vdbs/badge.blade.php',
            'files' => [
                'resources/views/components/vdbs/badge.blade.php',
            ],
            'tags' => [
                'badge',
                'label',
                'kennzeichnung',
            ],
            'usage' => [
                'Für kurze zusätzliche Kennzeichnungen neben einem Hauptinhalt.',
            ],
            'avoid' => [
                'Komplexe Statusinformationen ausschließlich als Badge kommunizieren.',
            ],
            'accessibility' => [
                'Text muss ohne Farbe verständlich sein.',
            ],
        ],
        [
            'id' => 'status',
            'name' => 'Status',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Semantische Statusdarstellung für neutral, info, success, warning und danger.',
            'source' => 'resources/views/components/vdbs/status.blade.php',
            'files' => [
                'resources/views/components/vdbs/status.blade.php',
                'resources/css/vdbs/components/page-states.css',
            ],
            'tags' => [
                'status',
                'zustand',
                'semantik',
            ],
            'usage' => [
                'Für persistente Zustände von Accounts, Datensätzen oder Prozessen.',
            ],
            'avoid' => [
                'Temporäre Systemmeldungen als Status darstellen; dafür Notice verwenden.',
            ],
            'accessibility' => [
                'Status immer textlich benennen und nicht nur durch Farbe darstellen.',
            ],
        ],
        [
            'id' => 'notice',
            'name' => 'Hinweise',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Semantische Hinweise und Feedbackmeldungen inklusive alert/status-Rollen.',
            'source' => 'resources/views/components/vdbs/notice.blade.php',
            'files' => [
                'resources/views/components/vdbs/notice.blade.php',
                'resources/css/vdbs/components/notices.css',
            ],
            'tags' => [
                'notice',
                'hinweis',
                'feedback',
                'alert',
            ],
            'usage' => [
                'Für Erfolg, Information, Warnung und Fehlerfeedback.',
            ],
            'avoid' => [
                'Jede normale Information als farbigen Hinweis hervorheben.',
            ],
            'accessibility' => [
                'Dynamisches wichtiges Feedback mit passender Live-/Alert-Semantik ausgeben.',
            ],
        ],
        [
            'id' => 'forms',
            'name' => 'Formulare',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Labels, Controls, Hilfetexte, Formularraster und Feldzustände.',
            'source' => 'resources/css/vdbs/components/forms.css',
            'files' => [
                'resources/css/vdbs/components/forms.css',
            ],
            'tags' => [
                'formular',
                'input',
                'select',
                'label',
            ],
            'usage' => [
                'Für Dateneingabe mit sichtbaren Labels und erklärenden Hilfetexten.',
            ],
            'avoid' => [
                'Placeholder als Ersatz für Labels oder uneinheitliche Feldabstände.',
            ],
            'accessibility' => [
                'Jedes Control benötigt eine programmatisch zugeordnete Beschriftung.',
            ],
        ],
        [
            'id' => 'validation-summary',
            'name' => 'Validierungsübersicht',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Zusammenfassung von Formularfehlern und klarer Einstieg in die Fehlerkorrektur.',
            'source' => 'resources/views/components/vdbs/validation-summary.blade.php',
            'files' => [
                'resources/views/components/vdbs/validation-summary.blade.php',
                'resources/css/vdbs/components/validation.css',
            ],
            'tags' => [
                'validierung',
                'formular',
                'fehler',
            ],
            'usage' => [
                'Bei Formularen mit mehreren möglichen Validierungsfehlern oberhalb des Formulars.',
            ],
            'avoid' => [
                'Fehler ausschließlich in einer globalen Meldung nennen und das betroffene Feld unmarkiert lassen.',
            ],
            'accessibility' => [
                'Fehler verständlich benennen und Fokus-/Verknüpfungslogik berücksichtigen.',
            ],
        ],
        [
            'id' => 'loading-state',
            'name' => 'Loading State',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Ruhige Ladeanzeige für verzögert eintreffende Inhalte.',
            'source' => 'resources/views/components/vdbs/loading-state.blade.php',
            'files' => [
                'resources/views/components/vdbs/loading-state.blade.php',
                'resources/css/vdbs/components/loading.css',
            ],
            'tags' => [
                'loading',
                'laden',
                'busy',
            ],
            'usage' => [
                'Bei asynchronen oder merklich verzögerten Inhaltszuständen.',
            ],
            'avoid' => [
                'Dauerhaft animierte Dekoration ohne echten Ladevorgang.',
            ],
            'accessibility' => [
                'Ladezustände müssen semantisch angekündigt und bei reduced motion ruhig bleiben.',
            ],
        ],
        [
            'id' => 'empty-state',
            'name' => 'Empty State',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Leerer Inhaltszustand mit Erklärung und optionaler nächster Aktion.',
            'source' => 'resources/views/components/vdbs/empty-state.blade.php',
            'files' => [
                'resources/views/components/vdbs/empty-state.blade.php',
                'resources/css/vdbs/components/empty-states.css',
            ],
            'tags' => [
                'empty',
                'leer',
                'kein-ergebnis',
            ],
            'usage' => [
                'Wenn eine Liste oder ein Bereich korrekt leer ist.',
            ],
            'avoid' => [
                'Technische Fehler als Empty State tarnen.',
            ],
            'accessibility' => [
                'Titel und Erklärung müssen den Zustand ohne Illustration verständlich machen.',
            ],
        ],
        [
            'id' => 'cards',
            'name' => 'Karten & Flächen',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Einfache Inhaltsflächen; bewusst sparsam und nicht als universelles Seitenlayout.',
            'source' => 'resources/css/vdbs/components/cards.css',
            'files' => [
                'resources/css/vdbs/components/cards.css',
            ],
            'tags' => [
                'card',
                'fläche',
                'gruppe',
            ],
            'usage' => [
                'Für klar abgegrenzte, zusammengehörige Informationseinheiten.',
            ],
            'avoid' => [
                'Jeden Abschnitt in eine Karte setzen oder generische SaaS-Dashboards nachbauen.',
            ],
            'accessibility' => [
                'Semantische HTML-Struktur darf nicht durch rein visuelle Karten ersetzt werden.',
            ],
        ],
        [
            'id' => 'links',
            'name' => 'Links',
            'category' => 'elements',
            'status' => 'stable',
            'description' => 'Textlinks und Linkvarianten für Navigation und weiterführende Inhalte.',
            'source' => 'resources/css/vdbs/components/links.css',
            'files' => [
                'resources/css/vdbs/components/links.css',
            ],
            'tags' => [
                'link',
                'navigation',
                'text',
            ],
            'usage' => [
                'Für Navigation zu einem Ziel oder einer Ressource.',
            ],
            'avoid' => [
                'Links als Aktion verwenden, wenn ein Button semantisch richtig ist.',
            ],
            'accessibility' => [
                'Linktexte müssen auch außerhalb des Satzkontexts verständlich sein.',
            ],
        ],
        [
            'id' => 'metadata',
            'name' => 'Metadaten',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Kompakte Schlüssel-Wert- und Zusatzinformationen zu Inhalten.',
            'source' => 'resources/css/vdbs/components/metadata.css',
            'files' => [
                'resources/css/vdbs/components/metadata.css',
            ],
            'tags' => [
                'metadaten',
                'datum',
                'autor',
                'eigenschaft',
            ],
            'usage' => [
                'Für ergänzende Fakten, die den Hauptinhalt beschreiben.',
            ],
            'avoid' => [
                'Wichtige Hauptinformation ausschließlich in kleinem Metatext verstecken.',
            ],
            'accessibility' => [
                'Reihenfolge und Beschriftungen müssen auch linear verständlich sein.',
            ],
        ],
        [
            'id' => 'media',
            'name' => 'Medien',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Darstellung von Bildern und medialen Inhaltsblöcken im redaktionellen Kontext.',
            'source' => 'resources/css/vdbs/components/media.css',
            'files' => [
                'resources/css/vdbs/components/media.css',
            ],
            'tags' => [
                'bild',
                'media',
                'caption',
            ],
            'usage' => [
                'Für redaktionelle Medien mit sinnvoller Einbettung in Inhalte.',
            ],
            'avoid' => [
                'Dekorative Bilder mit redundanten Alternativtexten versehen.',
            ],
            'accessibility' => [
                'Informative Bilder benötigen passende Alternativtexte; Bildunterschriften ergänzen Kontext.',
            ],
        ],
        [
            'id' => 'disclosure',
            'name' => 'Disclosure',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Ein- und ausklappbare Zusatzinformation mit nachvollziehbarem Zustand.',
            'source' => 'resources/css/vdbs/components/disclosure.css',
            'files' => [
                'resources/css/vdbs/components/disclosure.css',
            ],
            'tags' => [
                'disclosure',
                'aufklappen',
                'details',
            ],
            'usage' => [
                'Für optionale Zusatzinformation, die den Hauptfluss sonst überladen würde.',
            ],
            'avoid' => [
                'Essenzielle Information standardmäßig verstecken.',
            ],
            'accessibility' => [
                'Trigger benötigt sichtbaren Fokus und programmatisch erkennbaren expanded-Zustand.',
            ],
        ],
        [
            'id' => 'navigation',
            'name' => 'Navigation',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Lokale Navigation, Breadcrumbs und navigationsbezogene Muster.',
            'source' => 'resources/css/vdbs/components/navigation.css',
            'files' => [
                'resources/css/vdbs/components/navigation.css',
            ],
            'tags' => [
                'navigation',
                'breadcrumb',
                'menü',
            ],
            'usage' => [
                'Für Orientierung innerhalb eines klar definierten Informationsraums.',
            ],
            'avoid' => [
                'Neue parallele Navigationssysteme ohne nachvollziehbare Informationsarchitektur.',
            ],
            'accessibility' => [
                'Aktuelle Seite und Navigationszweck semantisch kennzeichnen.',
            ],
        ],
        [
            'id' => 'search-filter',
            'name' => 'Suche & Filter',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Suchfelder, Filtergruppen und Ergebnissteuerung.',
            'source' => 'resources/css/vdbs/components/search.css',
            'files' => [
                'resources/css/vdbs/components/search.css',
            ],
            'tags' => [
                'suche',
                'filter',
                'ergebnisse',
            ],
            'usage' => [
                'Für größere Daten- oder Inhaltsmengen mit klaren Suchkriterien.',
            ],
            'avoid' => [
                'Filter anbieten, die keine erkennbare Wirkung oder keinen Rücksetzweg haben.',
            ],
            'accessibility' => [
                'Filter müssen per Tastatur bedienbar und ihr aktueller Zustand erkennbar sein.',
            ],
        ],
        [
            'id' => 'tables',
            'name' => 'Tabellen',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Strukturierte Datentabellen mit Toolbar, Überschriften und responsiver Einbettung.',
            'source' => 'resources/css/vdbs/components/tables.css',
            'files' => [
                'resources/css/vdbs/components/tables.css',
            ],
            'tags' => [
                'tabelle',
                'daten',
                'liste',
            ],
            'usage' => [
                'Für echte tabellarische Beziehungen und vergleichbare Datensätze.',
            ],
            'avoid' => [
                'Layout mit Tabellen bauen oder kleine einfache Listen unnötig tabellarisch darstellen.',
            ],
            'accessibility' => [
                'Spalten-/Zeilenüberschriften semantisch auszeichnen und Tabellenzweck nachvollziehbar halten.',
            ],
        ],
        [
            'id' => 'dialog',
            'name' => 'Dialog',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Modale Bestätigung oder fokussierte Interaktion auf einer übergeordneten Seite.',
            'source' => 'resources/views/components/vdbs/dialog.blade.php',
            'files' => [
                'resources/views/components/vdbs/dialog.blade.php',
                'resources/css/vdbs/components/dialogs.css',
            ],
            'tags' => [
                'dialog',
                'modal',
                'bestätigung',
            ],
            'usage' => [
                'Für kurze fokussierte Entscheidungen, die den aktuellen Kontext nicht verlassen sollen.',
            ],
            'avoid' => [
                'Lange Formulare oder komplette Seitenabläufe in Dialoge verschieben.',
            ],
            'accessibility' => [
                'Fokusmanagement, Escape-Schließen und Rückgabe des Fokus müssen funktionieren.',
            ],
        ],
        [
            'id' => 'danger-zone',
            'name' => 'Danger Zone',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Deutlich abgesetzter Bereich für destruktive oder besonders sensible Aktionen.',
            'source' => 'resources/views/components/vdbs/danger-zone.blade.php',
            'files' => [
                'resources/views/components/vdbs/danger-zone.blade.php',
                'resources/css/vdbs/components/danger.css',
            ],
            'tags' => [
                'danger',
                'destruktiv',
                'verwaltung',
            ],
            'usage' => [
                'Für Löschen, Deaktivieren oder irreversible Änderungen mit hoher Tragweite.',
            ],
            'avoid' => [
                'Normale Bearbeitungsaktionen dramatisieren.',
            ],
            'accessibility' => [
                'Konsequenz und Ziel der Aktion müssen vor Ausführung textlich verständlich sein.',
            ],
        ],
        [
            'id' => 'records',
            'name' => 'Datensatzlisten',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Wiederverwendbare Darstellung strukturierter Datensätze und Key-Facts.',
            'source' => 'resources/css/vdbs/components/records.css',
            'files' => [
                'resources/css/vdbs/components/records.css',
            ],
            'tags' => [
                'datensatz',
                'liste',
                'key-facts',
            ],
            'usage' => [
                'Für kompakte strukturierte Informationen in Portal und Verwaltung.',
            ],
            'avoid' => [
                'Unstrukturierte Fließtexte in Datensatzraster pressen.',
            ],
            'accessibility' => [
                'Beschriftung und Wert müssen auch in linearer Reihenfolge eindeutig zugeordnet bleiben.',
            ],
        ],
        [
            'id' => 'file-item',
            'name' => 'Datei & Download',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Dateieintrag mit Name, Dateiinformation und Download-Aktion.',
            'source' => 'resources/views/components/vdbs/file-item.blade.php',
            'files' => [
                'resources/views/components/vdbs/file-item.blade.php',
                'resources/css/vdbs/components/files.css',
            ],
            'tags' => [
                'datei',
                'download',
                'dokument',
            ],
            'usage' => [
                'Für bereitgestellte Dokumente und Downloads.',
            ],
            'avoid' => [
                'Dateigröße oder Dateityp verschweigen, wenn sie für die Nutzung relevant sind.',
            ],
            'accessibility' => [
                'Linktext soll Ziel und möglichst Dateityp verständlich machen.',
            ],
        ],
        [
            'id' => 'news-teaser',
            'name' => 'News-Teaser',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Redaktioneller Teaser für Nachrichten und Artikel.',
            'source' => 'resources/views/components/vdbs/news-teaser.blade.php',
            'files' => [
                'resources/views/components/vdbs/news-teaser.blade.php',
                'resources/css/vdbs/components/editorial.css',
            ],
            'tags' => [
                'news',
                'artikel',
                'teaser',
                'redaktion',
            ],
            'usage' => [
                'Für Nachrichtenlisten und Einstiege in längere Artikel.',
            ],
            'avoid' => [
                'Den vollständigen Artikeltext in Teaserlisten wiederholen.',
            ],
            'accessibility' => [
                'Überschrift und Linkziel müssen den Beitrag eindeutig identifizieren.',
            ],
        ],
        [
            'id' => 'event-teaser',
            'name' => 'Veranstaltungsteaser',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Kompakte Veranstaltungsdarstellung mit Termin- und Ortsinformation.',
            'source' => 'resources/views/components/vdbs/event-teaser.blade.php',
            'files' => [
                'resources/views/components/vdbs/event-teaser.blade.php',
                'resources/css/vdbs/components/events.css',
            ],
            'tags' => [
                'event',
                'veranstaltung',
                'termin',
                'teaser',
            ],
            'usage' => [
                'Für Veranstaltungslisten, Übersichten und kontextuelle Empfehlungen.',
            ],
            'avoid' => [
                'Vollständige Veranstaltungsdetailseiten durch den Teaser ersetzen.',
            ],
            'accessibility' => [
                'Datum und Zeit verständlich ausschreiben; Ort und Aktion eindeutig benennen.',
            ],
        ],
        [
            'id' => 'contact-block',
            'name' => 'Kontaktblock',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Kontaktinformation für Ansprechpartner:innen und zuständige Stellen.',
            'source' => 'resources/views/components/vdbs/contact-block.blade.php',
            'files' => [
                'resources/views/components/vdbs/contact-block.blade.php',
                'resources/css/vdbs/components/contacts.css',
            ],
            'tags' => [
                'kontakt',
                'ansprechperson',
                'email',
                'telefon',
            ],
            'usage' => [
                'Für direkte Ansprechpartner:innen im Kontext eines Inhalts oder Prozesses.',
            ],
            'avoid' => [
                'Kontaktinformationen ohne erkennbare Zuständigkeit präsentieren.',
            ],
            'accessibility' => [
                'E-Mail und Telefon müssen als verständliche, nutzbare Kontaktziele ausgegeben werden.',
            ],
        ],
        [
            'id' => 'resource-item',
            'name' => 'Ressourceneintrag',
            'category' => 'content',
            'status' => 'stable',
            'description' => 'Eintrag für interne oder externe Ressourcen und weiterführende Angebote.',
            'source' => 'resources/views/components/vdbs/resource-item.blade.php',
            'files' => [
                'resources/views/components/vdbs/resource-item.blade.php',
                'resources/css/vdbs/components/resources.css',
            ],
            'tags' => [
                'ressource',
                'link',
                'material',
            ],
            'usage' => [
                'Für kuratierte Material- und Ressourcenlisten.',
            ],
            'avoid' => [
                'Unkommentierte Linklisten ohne erkennbaren Nutzen.',
            ],
            'accessibility' => [
                'Externe Ziele und Inhaltstypen verständlich benennen.',
            ],
        ],
        [
            'id' => 'portal-header',
            'name' => 'Portal Header',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Zentraler horizontaler Portal-Header mit Bereichen, Navigation, Konto und Mobile-Menü.',
            'source' => 'resources/views/components/vdbs/portal-header.blade.php',
            'files' => [
                'resources/views/components/vdbs/portal-header.blade.php',
                'resources/css/vdbs/parts/header.css',
                'resources/js/portal-header.js',
            ],
            'tags' => [
                'header',
                'navigation',
                'konto',
                'portal',
            ],
            'usage' => [
                'Als gemeinsame Kopfzeile für Portal-, Design- und Verwaltungsbereiche.',
            ],
            'avoid' => [
                'Fachbereiche mit abweichenden permanenten Sidebars oder eigenen Header-Systemen ausstatten.',
            ],
            'accessibility' => [
                'Dropdowns und Mobile-Menü müssen mit Tastatur, Fokus und Escape funktionieren.',
            ],
        ],
        [
            'id' => 'portal-footer',
            'name' => 'Portal Footer',
            'category' => 'patterns',
            'status' => 'stable',
            'description' => 'Gemeinsamer Footer mit Informations-, Service- und Metanavigation.',
            'source' => 'resources/views/components/vdbs/portal-footer.blade.php',
            'files' => [
                'resources/views/components/vdbs/portal-footer.blade.php',
                'resources/css/vdbs/parts/footer.css',
            ],
            'tags' => [
                'footer',
                'navigation',
                'service',
            ],
            'usage' => [
                'Als gemeinsamer Seitenabschluss in den Portalbereichen.',
            ],
            'avoid' => [
                'Pro Fachseite eigene Footer-Strukturen einführen.',
            ],
            'accessibility' => [
                'Linkgruppen klar beschriften und logische Überschriftenhierarchie verwenden.',
            ],
        ],
        [
            'id' => 'template-article',
            'name' => 'Vorlage · Artikel',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für längere redaktionelle Artikelseiten.',
            'source' => 'resources/views/design/pages/vorlagen/artikel.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/artikel.blade.php',
            ],
            'tags' => [
                'vorlage',
                'artikel',
                'redaktion',
            ],
            'usage' => [
                'Als Ausgangspunkt für redaktionelle Detailseiten.',
            ],
            'avoid' => [
                'Fachformulare oder Datensatzdetails in Artikelstruktur zwingen.',
            ],
            'accessibility' => [
                'Überschriftenhierarchie, Linktexte und Medienalternativen vollständig halten.',
            ],
        ],
        [
            'id' => 'template-form',
            'name' => 'Vorlage · Formular',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für vollständige Formularseiten mit Hilfen und Validierung.',
            'source' => 'resources/views/design/pages/vorlagen/formular.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/formular.blade.php',
            ],
            'tags' => [
                'vorlage',
                'formular',
            ],
            'usage' => [
                'Als Ausgangspunkt für neue fachliche Formularseiten.',
            ],
            'avoid' => [
                'Validierungs- und Hilfetextmuster pro Formular neu erfinden.',
            ],
            'accessibility' => [
                'Labels, Fehlermeldungen und Fokusreihenfolge vollständig prüfen.',
            ],
        ],
        [
            'id' => 'template-public-overview',
            'name' => 'Vorlage · Öffentliche Übersicht',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für öffentliche Übersichts- und Einstiegsseiten.',
            'source' => 'resources/views/design/pages/vorlagen/oeffentliche-uebersicht.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/oeffentliche-uebersicht.blade.php',
            ],
            'tags' => [
                'vorlage',
                'übersicht',
                'öffentlich',
            ],
            'usage' => [
                'Für öffentliche Übersichten mit klarer Informationshierarchie.',
            ],
            'avoid' => [
                'SaaS-Dashboard-Kartenraster ohne inhaltliche Notwendigkeit.',
            ],
            'accessibility' => [
                'Hauptüberschrift und Navigationsziele klar strukturieren.',
            ],
        ],
        [
            'id' => 'template-event',
            'name' => 'Vorlage · Veranstaltung',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für Veranstaltungsdetailseiten.',
            'source' => 'resources/views/design/pages/vorlagen/veranstaltung.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/veranstaltung.blade.php',
            ],
            'tags' => [
                'vorlage',
                'veranstaltung',
                'event',
            ],
            'usage' => [
                'Für vollständige Termin- und Veranstaltungsinformationen.',
            ],
            'avoid' => [
                'Terminmetadaten nur visuell ohne textliche Struktur darstellen.',
            ],
            'accessibility' => [
                'Datum, Zeit, Ort und Anmeldung klar und linear verständlich machen.',
            ],
        ],
        [
            'id' => 'template-admin-list',
            'name' => 'Vorlage · Verwaltungsliste',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für such- und filterbare Verwaltungslisten.',
            'source' => 'resources/views/design/pages/vorlagen/verwaltung-liste.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/verwaltung-liste.blade.php',
            ],
            'tags' => [
                'vorlage',
                'verwaltung',
                'liste',
            ],
            'usage' => [
                'Für administrative Datensatzübersichten.',
            ],
            'avoid' => [
                'Schreibaktionen ohne Berechtigungs- und Statuskontext direkt in Listen verteilen.',
            ],
            'accessibility' => [
                'Tabellenüberschriften, Filter und Aktionen eindeutig beschriften.',
            ],
        ],
        [
            'id' => 'template-admin-detail',
            'name' => 'Vorlage · Verwaltungsdetail',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Referenz für administrative Detail- und Bearbeitungsseiten.',
            'source' => 'resources/views/design/pages/vorlagen/verwaltung-detail.blade.php',
            'files' => [
                'resources/views/design/pages/vorlagen/verwaltung-detail.blade.php',
            ],
            'tags' => [
                'vorlage',
                'verwaltung',
                'detail',
            ],
            'usage' => [
                'Für Konten, Personen und andere strukturierte Verwaltungsdatensätze.',
            ],
            'avoid' => [
                'Destruktive Aktionen ohne abgesetzte Danger Zone anbieten.',
            ],
            'accessibility' => [
                'Status, Felder und Aktionen in nachvollziehbarer Reihenfolge anordnen.',
            ],
        ],
        [
            'id' => 'error-page',
            'name' => 'Fehlerseiten',
            'category' => 'templates',
            'status' => 'stable',
            'description' => 'Gemeinsames System für 403, 404, 419, 500 und 503.',
            'source' => 'resources/views/components/vdbs/error-page.blade.php',
            'files' => [
                'resources/views/components/vdbs/error-page.blade.php',
                'resources/css/vdbs/components/error-pages.css',
                'resources/views/errors/layout.blade.php',
                'resources/views/errors/403.blade.php',
                'resources/views/errors/404.blade.php',
                'resources/views/errors/419.blade.php',
                'resources/views/errors/500.blade.php',
                'resources/views/errors/503.blade.php',
            ],
            'tags' => [
                'fehler',
                'http',
                '404',
                '500',
            ],
            'usage' => [
                'Für zentrale HTTP-Fehlerzustände mit einem klaren Rückweg.',
            ],
            'avoid' => [
                'Stacktraces, interne IDs oder technische Details an Endnutzer:innen ausgeben.',
            ],
            'accessibility' => [
                'Fehler verständlich erklären und eine eindeutige nächste Aktion anbieten.',
            ],
        ],
        [
            'id' => 'web-content-library',
            'name' => 'Web Content Bibliothek',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Zentrale Registry, Suche, Filter und Inventarübersicht des Designsystems.',
            'source' => 'config/web_content_library.php',
            'files' => [
                'config/web_content_library.php',
                'app/Support/WebContentLibrary.php',
                'app/Support/WebContentLibraryReference.php',
                'resources/views/design/pages/bibliothek/index.blade.php',
            ],
            'tags' => [
                'bibliothek',
                'registry',
                'inventar',
            ],
            'usage' => [
                'Als zentrale Quelle für vorhandene Bausteine und deren Lebenszyklus.',
            ],
            'avoid' => [
                'Neue stabile Bausteine außerhalb der Registry dauerhaft weiterentwickeln.',
            ],
            'accessibility' => [
                'Bibliothekswerkzeuge selbst müssen dieselben Standards wie Produktseiten erfüllen.',
            ],
        ],
        [
            'id' => 'code-example',
            'name' => 'Code-Beispiel & Copy',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Gerenderte Vorschau und kopierbares Blade-Snippet in einem gemeinsamen Muster.',
            'source' => 'resources/views/components/vdbs/code-example.blade.php',
            'files' => [
                'resources/views/components/vdbs/code-example.blade.php',
                'resources/css/vdbs/components/code-examples.css',
                'resources/js/content-library.js',
                'resources/views/design/pages/bibliothek/code.blade.php',
            ],
            'tags' => [
                'code',
                'snippet',
                'copy',
            ],
            'usage' => [
                'Für dokumentierte Beispiele, die direkt in Fachseiten übernommen werden können.',
            ],
            'avoid' => [
                'Ungeprüfte oder veraltete Snippets als Referenz stehen lassen.',
            ],
            'accessibility' => [
                'Copy-Feedback wird über eine Live-Region angekündigt.',
            ],
        ],
        [
            'id' => 'icon-browser',
            'name' => 'Icon-Browser',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Durchsuchbare Übersicht der zentralen Icon-Definitionen mit kopierbaren Snippets.',
            'source' => 'app/Support/VdbsIconCatalog.php',
            'files' => [
                'app/Support/VdbsIconCatalog.php',
                'resources/css/vdbs/components/icon-browser.css',
                'resources/views/design/pages/bibliothek/icons.blade.php',
            ],
            'tags' => [
                'icon',
                'browser',
                'suche',
            ],
            'usage' => [
                'Vor Einsatz oder Erweiterung eines Icons prüfen, ob ein passendes Symbol bereits existiert.',
            ],
            'avoid' => [
                'Parallele Icon-Sammlungen anlegen.',
            ],
            'accessibility' => [
                'Semantische Nutzung eines Icons ist wichtiger als die reine Form.',
            ],
        ],
        [
            'id' => 'button-playground',
            'name' => 'Button-Playground',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Konfigurator für bestehende Button-Varianten mit Vorschau und Codeausgabe.',
            'source' => 'app/Support/ButtonExampleBuilder.php',
            'files' => [
                'app/Support/ButtonExampleBuilder.php',
                'resources/css/vdbs/components/playground.css',
                'resources/views/design/pages/bibliothek/buttons.blade.php',
            ],
            'tags' => [
                'button',
                'playground',
                'generator',
            ],
            'usage' => [
                'Bestehende Button-Varianten kombinieren und passenden Beispielcode erzeugen.',
            ],
            'avoid' => [
                'Für jede Kombination eine neue Button-Komponente erzeugen.',
            ],
            'accessibility' => [
                'Semantik von Link und Button muss passend zur ausgelösten Handlung gewählt werden.',
            ],
        ],
        [
            'id' => 'semantic-playground',
            'name' => 'Status- und Hinweis-Playground',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Generator für vorhandene Notice-, Status- und Badge-Varianten.',
            'source' => 'app/Support/SemanticExampleBuilder.php',
            'files' => [
                'app/Support/SemanticExampleBuilder.php',
                'resources/views/design/pages/bibliothek/semantic.blade.php',
            ],
            'tags' => [
                'status',
                'notice',
                'badge',
                'playground',
            ],
            'usage' => [
                'Semantische Varianten vergleichen und kopierbaren Blade-Code erzeugen.',
            ],
            'avoid' => [
                'Neue semantische Farben oder Zustände ohne echten Anwendungsfall erfinden.',
            ],
            'accessibility' => [
                'Warnungen und Fehler verwenden eine passende Alert-Semantik; Farbe bleibt nie die einzige Information.',
            ],
        ],
        [
            'id' => 'library-tooling',
            'name' => 'Bibliotheks-Werkzeuge',
            'category' => 'tools',
            'status' => 'beta',
            'description' => 'Artisan-Generatoren und Integritätsprüfung für Komponenten, Muster und Registry.',
            'source' => 'app/Console/Commands/VdbsLibraryCheckCommand.php',
            'files' => [
                'app/Console/Commands/VdbsLibraryCheckCommand.php',
                'app/Console/Commands/VdbsDesignStatusCommand.php',
                'app/Console/Commands/MakeVdbsComponentCommand.php',
                'app/Console/Commands/MakeVdbsPatternCommand.php',
            ],
            'tags' => [
                'artisan',
                'generator',
                'check',
                'qualität',
            ],
            'usage' => [
                'Neue Komponenten/Muster strukturiert beginnen und Registry vor Abschluss prüfen.',
            ],
            'avoid' => [
                'Generatorausgabe ungeprüft als fertige produktive Komponente betrachten.',
            ],
            'accessibility' => [
                'Generatoren erinnern an Dokumentation und Tests, ersetzen aber keine Accessibility-Prüfung.',
            ],
        ],
    ],
];
