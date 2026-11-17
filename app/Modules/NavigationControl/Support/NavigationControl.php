<?php

namespace App\Modules\NavigationControl\Support;

use App\Modules\NavigationControl\Models\NavigationArea;
use App\Modules\NavigationControl\Models\NavigationItem;
use App\Support\PortalAreas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Welche Bereiche und Seiten im Kopfmenü erscheinen und in welcher Reihenfolge (Web-Control unter Admins › Navigation). Standard: der Katalog
 * aus PortalNavigation, unverändert. Das betrifft nur die Darstellung; Rollen und Berechtigungen prüft weiterhin jede Seite selbst.
 * Start, Konto, der Bereich Admins, die Übersichtsseite jedes Bereichs und die Seite „Navigation“ lassen sich nicht ausblenden (kein Aussperren).
 */
final class NavigationControl
{
    /** @var Collection<string, NavigationArea>|null */
    private static ?Collection $areas = null;

    /** @var Collection<string, Collection<int, NavigationItem>>|null */
    private static ?Collection $items = null;

    /** Schlüssel einer Seite innerhalb ihres Bereichs (Route und Parameter; Gruppen: group:<Titel>). @param  array<string, mixed>  $page */
    public static function pageKey(array $page): string
    {
        if ($page['group'] ?? false) {
            return 'group:'.$page['label'];
        }

        return $page['route'].(isset($page['params']) ? '?'.http_build_query((array) $page['params']) : '');
    }

    /** Bereiche, die sich bearbeiten lassen. @return list<PortalAreas> */
    public static function togglableAreas(): array
    {
        return array_values(array_filter(PortalAreas::cases(), fn (PortalAreas $area): bool => ! in_array($area, [PortalAreas::Start, PortalAreas::Konto, PortalAreas::Admins], true)));
    }

    /** Seiten, die nie ausgeblendet werden. @param  array<string, mixed>  $page */
    public static function pinned(array $page): bool
    {
        return ($page['label'] ?? '') === 'Übersicht' || ($page['route'] ?? '') === 'navigation.edit';
    }

    /** @param  'header'|'lists'  $context */
    public static function areaEnabled(PortalAreas $area, string $context = 'header'): bool
    {
        $row = self::areas()->get($area->value);

        return $row === null || ($context === 'lists' ? $row->show_lists : $row->show_header);
    }

    /** Darf eine Person mit diesen Rollen den Bereich sehen? Ohne Auswahl der Verwaltung gelten allein die Regeln des Katalogs. @param  list<string>  $held */
    public static function rolesAllow(PortalAreas $area, array $held): bool
    {
        $roles = (array) self::areas()->get($area->value)?->roles;

        return $roles === [] || array_intersect($roles, $held) !== [];
    }

    public static function areaLabel(PortalAreas $area): string
    {
        return self::areas()->get($area->value)?->label ?: $area->label();
    }

    public static function areaSort(PortalAreas $area, int $default): int
    {
        return self::areas()->get($area->value)?->sort ?? ($default * 10);
    }

    /** Eintrag der Verwaltung zu einer Seite des Katalogs. */
    public static function item(PortalAreas $area, string $key): ?NavigationItem
    {
        return self::items()->get($area->value, collect())->first(fn (NavigationItem $item): bool => $item->page_key === $key);
    }

    /** @param  array<string, mixed>  $page */
    public static function pageEnabled(PortalAreas $area, array $page): bool
    {
        return self::item($area, self::pageKey($page))?->visible ?? true;
    }

    /**
     * Wendet Titel, Reihenfolge und eigene Links des Bereichs auf die sichtbaren Seiten an.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    public static function decorate(PortalAreas $area, array $pages): array
    {
        $rows = [];

        foreach ($pages as $index => $page) {
            $item = isset($page['key']) ? self::item($area, $page['key']) : null;

            if ($item !== null && filled($item->label) && $page['label'] !== 'Übersicht') {
                $page['label'] = $item->label;
            }

            $rows[] = ['sort' => ($item?->sort ?? ($index * 10)), 'page' => $page];
        }

        $custom = self::items()->get($area->value, collect())->filter(fn (NavigationItem $item): bool => $item->page_key === null && $item->parent_id === null && $item->visible && filled($item->url));

        foreach ($custom as $item) {
            $children = self::items()->get($area->value, collect())->filter(fn (NavigationItem $child): bool => $child->parent_id === $item->id && $child->visible && filled($child->url))->sortBy('sort')->map(fn (NavigationItem $child): array => self::customPage($child, []))->values()->all();
            $rows[] = ['sort' => $item->sort ?? 1000, 'page' => self::customPage($item, $children)];
        }

        usort($rows, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $row): array => $row['page'], $rows);
    }

    /** Alle Einträge der Verwaltung für einen Bereich, ersetzt durch die neue Fassung (eigene Links mit Unterseiten). @param  list<array<string, mixed>>  $catalogRows @param  list<array<string, mixed>>  $customRows */
    public static function saveItems(PortalAreas $area, array $catalogRows, array $customRows): void
    {
        NavigationItem::query()->where('area', $area->value)->delete();

        foreach ($catalogRows as $row) {
            NavigationItem::query()->create(['area' => $area->value, 'page_key' => $row['key'], 'label' => $row['label'], 'sort' => $row['sort'], 'visible' => $row['visible']]);
        }

        foreach ($customRows as $index => $row) {
            $parent = NavigationItem::query()->create(['area' => $area->value, 'label' => $row['label'], 'url' => $row['url'], 'sort' => 1000 + $index, 'visible' => true]);

            foreach ($row['children'] as $childIndex => $child) {
                NavigationItem::query()->create(['area' => $area->value, 'parent_id' => $parent->id, 'label' => $child['label'], 'url' => $child['url'], 'sort' => $childIndex, 'visible' => true]);
            }
        }

        self::reset();
    }

    public static function reset(): void
    {
        self::$areas = null;
        self::$items = null;
    }

    /** @param  list<array<string, mixed>>  $children @return array<string, mixed> */
    private static function customPage(NavigationItem $item, array $children): array
    {
        return ['key' => null, 'label' => (string) $item->label, 'description' => null, 'icon' => null, 'href' => (string) $item->url, 'current' => false, 'active' => false, 'group' => false, 'children' => $children];
    }

    /** @return Collection<string, NavigationArea> */
    private static function areas(): Collection
    {
        self::load();

        return self::$areas;
    }

    /** @return Collection<string, Collection<int, NavigationItem>> */
    private static function items(): Collection
    {
        self::load();

        return self::$items;
    }

    private static function load(): void
    {
        if (self::$areas !== null) {
            return;
        }

        try {
            if (Schema::hasTable('navigation_areas') && Schema::hasTable('navigation_items')) {
                self::$areas = NavigationArea::query()->get()->keyBy('area');
                self::$items = NavigationItem::query()->get()->groupBy('area');

                return;
            }
        } catch (Throwable) {
            // ohne Datenbank (z. B. Fehlerseiten) gilt der Katalog unverändert
        }

        self::$areas = collect();
        self::$items = collect();
    }
}
