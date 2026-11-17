<?php

namespace App\Modules\NavigationControl\Support;

use App\Modules\NavigationControl\Models\NavigationHidden;
use App\Support\PortalAreas;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Welche Bereiche und Seiten im Kopfmenü erscheinen. Standard: alles. Die Verwaltung (Admins › Navigation) blendet Bereiche und Seiten aus,
 * die noch nicht bestätigt sind. Das betrifft nur die Darstellung der Menüs und Startseiten; Rollen und Berechtigungen prüft weiterhin jede Seite selbst.
 * Start, Konto, der Bereich Admins und die Seite „Navigation“ lassen sich nicht ausblenden (kein Aussperren).
 */
final class NavigationControl
{
    /** @var list<string>|null */
    private static ?array $hidden = null;

    public static function areaKey(PortalAreas $area): string
    {
        return 'area:'.$area->value;
    }

    /** @param  array<string, mixed>  $page */
    public static function pageKey(PortalAreas $area, array $page): string
    {
        $params = isset($page['params']) ? '?'.http_build_query((array) $page['params']) : '';

        return 'page:'.$area->value.':'.$page['route'].$params;
    }

    public static function areaEnabled(PortalAreas $area): bool
    {
        return ! self::isHidden(self::areaKey($area));
    }

    /** @param  array<string, mixed>  $page */
    public static function pageEnabled(PortalAreas $area, array $page): bool
    {
        return ! self::isHidden(self::pageKey($area, $page));
    }

    /** Bereiche, die ausgeblendet werden dürfen. */
    public static function togglableAreas(): array
    {
        return array_values(array_filter(PortalAreas::cases(), fn (PortalAreas $area): bool => ! in_array($area, [PortalAreas::Start, PortalAreas::Konto, PortalAreas::Admins], true)));
    }

    /** Seiten, die nie ausgeblendet werden (Übersicht des Bereichs, Navigation selbst). */
    public static function pinned(array $page): bool
    {
        return ($page['label'] ?? '') === 'Übersicht' || ($page['route'] ?? '') === 'navigation.edit';
    }

    /** @param  list<string>  $keys */
    public static function replace(array $keys): void
    {
        NavigationHidden::query()->delete();

        foreach (array_unique($keys) as $key) {
            NavigationHidden::query()->create(['key' => $key]);
        }

        self::reset();
    }

    public static function reset(): void
    {
        self::$hidden = null;
    }

    private static function isHidden(string $key): bool
    {
        if (self::$hidden === null) {
            try {
                self::$hidden = Schema::hasTable('navigation_hidden') ? NavigationHidden::query()->pluck('key')->all() : [];
            } catch (Throwable) {
                self::$hidden = [];
            }
        }

        return in_array($key, self::$hidden, true);
    }
}
