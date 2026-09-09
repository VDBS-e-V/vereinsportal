<?php

namespace App\Support;

final class WebContentLibraryReference
{
    /**
     * @var array<string, string>
     */
    private const DESIGN_PATHS = [
        'design-tokens' => '/design/grundlagen',
        'base-typography' => '/design/grundlagen',
        'layout-system' => '/design/layout',
        'accessibility' => '/design/grundlagen',
        'print-system' => '/design/print',
        'button' => '/design/elemente/buttons',
        'icon' => '/design/elemente/icons',
        'badge' => '/design/elemente/hinweise',
        'status' => '/design/elemente/hinweise',
        'notice' => '/design/elemente/hinweise',
        'forms' => '/design/elemente/formulare',
        'validation-summary' => '/design/elemente/validierung',
        'loading-state' => '/design/elemente/loading',
        'empty-state' => '/design/elemente/empty-states',
        'metadata' => '/design/elemente/metadaten',
        'media' => '/design/elemente/medien',
        'disclosure' => '/design/elemente/disclosure',
        'navigation' => '/design/elemente/navigation',
        'search-filter' => '/design/elemente/suche-filter',
        'tables' => '/design/elemente/tabellen',
        'dialog' => '/design/elemente/dialoge',
        'danger-zone' => '/design/elemente/bestaetigung',
        'records' => '/design/elemente/datensatzlisten',
        'file-item' => '/design/elemente/dateien',
        'news-teaser' => '/design/elemente/artikel-news',
        'event-teaser' => '/design/elemente/veranstaltungen',
        'contact-block' => '/design/elemente/kontakte',
        'resource-item' => '/design/elemente/ressourcen',
        'portal-header' => '/design/header',
        'template-article' => '/design/vorlagen/artikel',
        'template-form' => '/design/vorlagen/formular',
        'template-public-overview' => '/design/vorlagen/oeffentliche-uebersicht',
        'template-event' => '/design/vorlagen/veranstaltung',
        'template-admin-list' => '/design/vorlagen/verwaltung-liste',
        'template-admin-detail' => '/design/vorlagen/verwaltung-detail',
        'error-page' => '/design/fehlerseiten',
        'web-content-library' => '/design/bibliothek',
        'code-example' => '/design/bibliothek/code',
        'icon-browser' => '/design/bibliothek/icons',
        'button-playground' => '/design/bibliothek/playground/buttons',
    ];

    /**
     * @var array<string, string>
     */
    private const CODE_EXAMPLES = [
        'button' => '<button class="btn" type="button">Speichern</button>',
        'icon' => '<x-vdbs.icon name="calendar" size="20" />',
        'badge' => '<x-vdbs.badge tone="accent">Neu</x-vdbs.badge>',
        'status' => '<x-vdbs.status type="success">Aktiv</x-vdbs.status>',
        'notice' => '<x-vdbs.notice type="warning" role="alert">Bitte prüfen.</x-vdbs.notice>',
        'loading-state' => '<x-vdbs.loading-state label="Wird geladen" live />',
        'empty-state' => '<x-vdbs.empty-state title="Keine Einträge" description="Es liegen noch keine Daten vor." />',
        'file-item' => '<x-vdbs.file-item name="Dokument.pdf" href="#" meta="PDF · 240 KB" />',
        'event-teaser' => '<x-vdbs.event-teaser title="Veranstaltung" href="#" date="12.11.2026" />',
        'error-page' => '<x-vdbs.error-page code="404" title="Seite nicht gefunden" description="Die gewünschte Seite ist nicht verfügbar." />',
    ];

    public function for(
        array $item,
    ): array {
        $id = (string) ($item['id'] ?? '');

        return [
            'design_url' => isset(
                self::DESIGN_PATHS[$id]
            )
                ? url(self::DESIGN_PATHS[$id])
                : null,
            'code' => self::CODE_EXAMPLES[$id]
                ?? null,
        ];
    }
}
