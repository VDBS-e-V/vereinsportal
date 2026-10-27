<?php

namespace App\Modules\DesignLibrary\Support;

use App\Modules\DesignLibrary\Enums\LibraryCategory;
use App\Modules\DesignLibrary\Enums\LifecycleStatus;
use Illuminate\Support\Collection;

/**
 * Zentrale Registry der Design-Bibliothek. Reine PHP-Konfiguration, keine DB.
 *
 * `view` verweist auf eine Blade-View unter design-library/entries/.
 */
final class Registry
{
    /**
     * @return Collection<string, array{slug: string, title: string, description: string, category: LibraryCategory, status: LifecycleStatus, view: string, styleguide: string}>
     */
    public static function all(): Collection
    {
        return collect(self::definitions())
            ->map(fn (array $entry): array => $entry + ['view' => 'design-library.entries.'.$entry['slug']])
            ->keyBy('slug');
    }

    public static function find(string $slug): ?array
    {
        return self::all()->get($slug);
    }

    /** @return Collection<string, Collection<string, array>> nach Kategorie-Wert gruppiert */
    public static function grouped(): Collection
    {
        return self::all()->groupBy(fn (array $entry): string => $entry['category']->value);
    }

    private static function definitions(): array
    {
        return [
            ['slug' => 'farben', 'title' => 'Farben & semantische Tokens', 'description' => 'Markenfarben, Gruppenfarben und die semantischen Tokens, die im Code verwendet werden.', 'category' => LibraryCategory::Tokens, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 2'],
            ['slug' => 'typografie', 'title' => 'Typografie', 'description' => 'Lato in 400/700/900, Größenskala und Zeilenhöhen.', 'category' => LibraryCategory::Tokens, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 2'],
            ['slug' => 'spacing-radius', 'title' => 'Spacing, Radius & Schatten', 'description' => '4-px-Raster; Ecken sind eckig (Radius 0), nur Pills und Avatare bleiben rund.', 'category' => LibraryCategory::Tokens, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 2'],
            ['slug' => 'buttons', 'title' => 'Buttons', 'description' => 'Primär, sekundär, Link und Gefahr.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 5.1'],
            ['slug' => 'formulare', 'title' => 'Formularfelder', 'description' => 'Input, Select und Textarea in den Zuständen normal, Fehler, Erfolg und deaktiviert.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 5.2'],
            ['slug' => 'karten', 'title' => 'Karten', 'description' => 'Standard, Info, Statement und Zitat.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 5.3'],
            ['slug' => 'navigation', 'title' => 'Navigation & Breadcrumb', 'description' => 'Hauptnavigation und Brotkrumen.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Beta, 'styleguide' => 'Kap. 5.4'],
            ['slug' => 'tabellen', 'title' => 'Tabellen', 'description' => 'Zebra-Streifen und sortierbare Spalten.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Beta, 'styleguide' => 'Kap. 5.5'],
            ['slug' => 'badges', 'title' => 'Badges', 'description' => 'Neutral, Erfolg, Warnung, Fehler und Kategorie 1–6.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 5.6'],
            ['slug' => 'hinweise', 'title' => 'Hinweise', 'description' => 'Info-, Erfolgs-, Warn- und Fehlerhinweise.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 5.7'],
            ['slug' => 'modal', 'title' => 'Modal', 'description' => 'Natives dialog-Element mit Fokus-Falle.', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Beta, 'styleguide' => 'Kap. 5.8'],
            ['slug' => 'hero', 'title' => 'Hero', 'description' => 'Großer Einstieg einer Seite in den Varianten Bild, Bildunterschrift und Panel mit Kacheln (Paket Medien).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 4'],
            ['slug' => 'abschnitt-split-raster', 'title' => 'Abschnitt, Split und Raster', 'description' => 'Seitenabschnitte (auch als graues Band), Text neben Bild und gleichmäßige Raster (Paket Layout).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 4'],
            ['slug' => 'quick-links', 'title' => 'Quick-Links', 'description' => 'Reihe farbig akzentuierter Kacheln für den Schnellzugriff (Paket Karten).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.3'],
            ['slug' => 'teaser', 'title' => 'Teaser', 'description' => 'Bild, Kategorie, Titel und Anreißer mit einem Link für die ganze Karte (Paket Karten).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.3'],
            ['slug' => 'kontaktblock', 'title' => 'Kontaktblock', 'description' => 'Bild, Text, Kontaktzeilen und Aktion für Kontaktabschnitte (Paket Karten).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.3'],
            ['slug' => 'chips', 'title' => 'Chips', 'description' => 'Liste von Links als Chips, z. B. Bereiche (Paket Daten).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.6'],
            ['slug' => 'figure', 'title' => 'Bild mit Unterschrift', 'description' => 'Bild mit Bildunterschrift und Bildnachweis (Paket Medien).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 4'],
            ['slug' => 'link-button', 'title' => 'Link-Button', 'description' => 'Links im Button-Stil: Pfeil, Linie und „Alle anzeigen“ (Paket Aktionen).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.1'],
            ['slug' => 'back-to-top', 'title' => 'Zum Seitenanfang', 'description' => 'Sprunglink zum Seitenanfang (Paket Aktionen).', 'category' => LibraryCategory::Components, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 5.1'],
            ['slug' => 'startseite', 'title' => 'Startseite (Muster)', 'description' => 'Zusammenspiel von Hero, Quick-Links, Abschnitten, Teasern, Split und Kontaktblock.', 'category' => LibraryCategory::Patterns, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 4'],
            ['slug' => 'gruppen-marken', 'title' => 'Gruppen-Marken', 'description' => 'Form, Farbe und Zahl 1–6; Zuordnung der Portal-Bereiche.', 'category' => LibraryCategory::BrandElements, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 9.2'],
            ['slug' => 'portal-bereiche', 'title' => 'Portal-Bereiche', 'description' => 'Die zehn Bereiche mit Icon, Label und Gruppen-Marke.', 'category' => LibraryCategory::Patterns, 'status' => LifecycleStatus::Experimental, 'styleguide' => 'Kap. 9.2'],
            ['slug' => 'barrierefreiheit', 'title' => 'Barrierefreiheit', 'description' => 'WCAG 2.1/2.2 AA: Kontrast, Fokus, Touch-Targets, keine Information nur über Farbe.', 'category' => LibraryCategory::Guidelines, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 3'],
            ['slug' => 'ton-sprache', 'title' => 'Ton & Sprache', 'description' => 'Ansprache, Schreibweise und Beschriftungen.', 'category' => LibraryCategory::Guidelines, 'status' => LifecycleStatus::Stable, 'styleguide' => 'Kap. 8'],
        ];
    }
}
