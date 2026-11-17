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
        return array_values(array_filter(PortalAreas::cases(), fn (PortalAreas $area): bool => ! in_array($area, [PortalAreas::Start, PortalAreas::Konto, PortalAreas::Admins, PortalAreas::WebControl], true)));
    }

    /** Seiten, die nie ausgeblendet werden. @param  array<string, mixed>  $page */
    public static function pinned(array $page): bool
    {
        return ($page['label'] ?? '') === 'Übersicht' || in_array($page['route'] ?? '', ['navigation.edit', 'navigation.headers'], true);
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
     * Wendet Titel, Reihenfolge, Verschachtelung und eigene Links des Bereichs auf die sichtbaren Seiten an. Die Reihenfolge der obersten Ebene ist die
     * waagerechte Position im Header, die der Unterseiten ihre senkrechte Position im Untermenü.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    public static function decorate(PortalAreas $area, array $pages): array
    {
        $items = self::items()->get($area->value, collect())->keyBy(fn (NavigationItem $item): string => (string) ($item->page_key ?? $item->ref));
        $entries = [];

        foreach ($pages as $index => $page) {
            $item = isset($page['key']) ? $items->get($page['key']) : null;

            if ($item !== null && filled($item->label) && $page['label'] !== 'Übersicht') {
                $page['label'] = $item->label;
            }

            $entries[(string) $page['key']] = ['page' => $page, 'sort' => $item?->sort ?? ($index * 10), 'parent' => $item?->parent_ref];
        }

        foreach ($items as $ref => $item) {
            if ($item->page_key === null && $item->visible && filled($item->url)) {
                $entries[(string) $ref] = ['page' => self::customPage($item), 'sort' => $item->sort ?? 1000, 'parent' => $item->parent_ref];
            }
        }

        // Eine Seite wird Unterseite, wenn ihre übergeordnete Seite vorhanden und selbst oberste Ebene ist und sie selbst keine Unterseiten bekommt
        $parents = collect($entries)->pluck('parent')->filter()->unique()->all();
        $kids = [];

        foreach ($entries as $ref => $entry) {
            $parent = $entry['parent'];

            if ($parent !== null && $parent !== $ref && isset($entries[$parent]) && $entries[$parent]['parent'] === null && ! in_array($ref, $parents, true) && ($entry['page']['group'] ?? false) !== true) {
                $kids[$parent][] = $ref;
                $entries[$ref]['nested'] = true;
            }
        }

        $top = [];

        foreach ($entries as $ref => $entry) {
            if (isset($entry['nested'])) {
                continue;
            }

            $page = $entry['page'];

            if (isset($kids[$ref])) {
                usort($kids[$ref], fn (string $a, string $b): int => $entries[$a]['sort'] <=> $entries[$b]['sort']);
                $page['children'] = [...$page['children'], ...array_map(fn (string $kid): array => $entries[$kid]['page'], $kids[$ref])];
            }

            $top[] = ['sort' => $entry['sort'], 'page' => $page];
        }

        usort($top, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $row): array => $row['page'], $top);
    }

    /**
     * Der Aufbau des Headers für den Editor: alle Seiten des Katalogs (ohne Übersicht) und die eigenen Links, oberste Ebene der Reihe nach, jeweils gefolgt von
     * ihren Unterseiten.
     *
     * @param  list<array<string, mixed>>  $catalogPages
     * @return list<array{ref: string, kind: string, title: string, label: string, url: string, visible: bool, parent: ?string, pinned: bool, nestable: bool, parentable: bool}>
     */
    public static function layout(PortalAreas $area, array $catalogPages): array
    {
        $items = self::items()->get($area->value, collect())->keyBy(fn (NavigationItem $item): string => (string) ($item->page_key ?? $item->ref));
        $entries = [];

        foreach ($catalogPages as $index => $page) {
            if (($page['label'] ?? '') === 'Übersicht') {
                continue;
            }

            $key = self::pageKey($page);
            $item = $items->get($key);
            $group = ($page['group'] ?? false) === true;
            $entries[$key] = ['sort' => $item?->sort ?? ($index * 10), 'parent' => $item?->parent_ref, 'row' => ['ref' => $key, 'kind' => 'catalog', 'title' => (string) $page['label'], 'label' => (string) ($item?->label ?? ''), 'url' => '', 'visible' => $item?->visible ?? true, 'parent' => $item?->parent_ref, 'pinned' => self::pinned($page) || $group, 'nestable' => ! $group && ($page['children'] ?? []) === [], 'parentable' => ! $group]];
        }

        foreach ($items as $ref => $item) {
            if ($item->page_key === null) {
                $entries[(string) $ref] = ['sort' => $item->sort ?? 1000, 'parent' => $item->parent_ref, 'row' => ['ref' => (string) $ref, 'kind' => 'custom', 'title' => '', 'label' => (string) $item->label, 'url' => (string) $item->url, 'visible' => $item->visible, 'parent' => $item->parent_ref, 'pinned' => false, 'nestable' => true, 'parentable' => true]];
            }
        }

        $topLevel = collect($entries)->filter(fn (array $entry): bool => $entry['parent'] === null || ! isset($entries[$entry['parent']]))->sortBy('sort');
        $rows = [];

        foreach ($topLevel as $ref => $entry) {
            $entry['row']['parent'] = null;
            $rows[] = $entry['row'];

            foreach (collect($entries)->filter(fn (array $child): bool => $child['parent'] === $ref)->sortBy('sort') as $child) {
                $rows[] = $child['row'];
            }
        }

        return $rows;
    }

    /**
     * Speichert den Aufbau des Headers: die Reihenfolge der Einträge ist die Position; „parent“ verweist auf die übergeordnete Seite.
     *
     * @param  list<array{ref: string, kind: string, label: string, url: string, visible: bool, parent: ?string}>  $rows
     */
    public static function saveLayout(PortalAreas $area, array $rows): void
    {
        $map = [];
        $next = 1;

        foreach ($rows as $row) {
            $map[$row['ref']] = $row['kind'] === 'catalog' ? $row['ref'] : 'c'.$next++;
        }

        NavigationItem::query()->where('area', $area->value)->delete();

        foreach ($rows as $index => $row) {
            NavigationItem::query()->create([
                'area' => $area->value,
                'ref' => $map[$row['ref']],
                'parent_ref' => $row['parent'] !== null ? ($map[$row['parent']] ?? null) : null,
                'page_key' => $row['kind'] === 'catalog' ? $row['ref'] : null,
                'label' => filled($row['label']) ? $row['label'] : null,
                'url' => $row['kind'] === 'custom' ? $row['url'] : null,
                'sort' => $index * 10,
                'visible' => $row['visible'],
            ]);
        }

        self::reset();
    }

    public static function reset(): void
    {
        self::$areas = null;
        self::$items = null;
    }

    /** @param  list<array<string, mixed>>  $children @return array<string, mixed> */
    private static function customPage(NavigationItem $item, array $children = []): array
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
