<?php

namespace App\Modules\DesignLibrary\Enums;

enum LibraryCategory: string
{
    case Tokens = 'tokens';
    case Components = 'components';
    case BrandElements = 'brand-elements';
    case Patterns = 'patterns';
    case Guidelines = 'guidelines';

    public function label(): string
    {
        return match ($this) {
            self::Tokens => 'Tokens',
            self::Components => 'Komponenten',
            self::BrandElements => 'Marken-Bausteine',
            self::Patterns => 'Muster',
            self::Guidelines => 'Richtlinien',
        };
    }
}
