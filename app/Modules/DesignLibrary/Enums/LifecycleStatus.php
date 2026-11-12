<?php

namespace App\Modules\DesignLibrary\Enums;

enum LifecycleStatus: string
{
    case Experimental = 'experimental';
    case Beta = 'beta';
    case Stable = 'stable';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::Experimental => 'Experimentell',
            self::Beta => 'Beta',
            self::Stable => 'Stabil',
            self::Deprecated => 'Veraltet',
        };
    }

    /** Badge-Variante der <x-vdbs.badge>-Komponente. */
    public function badgeTone(): string
    {
        return match ($this) {
            self::Experimental, self::Beta => 'warning',
            self::Stable => 'success',
            self::Deprecated => 'error',
        };
    }
}
