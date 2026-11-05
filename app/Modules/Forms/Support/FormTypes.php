<?php

namespace App\Modules\Forms\Support;

/** Die Arten von Anträgen mit Bezeichnung und Nummernpräfix. */
final class FormTypes
{
    public const TYPES = [
        'expense' => ['label' => 'Kostenrückerstattung', 'prefix' => 'E', 'hint' => 'Sie haben etwas ausgelegt und möchten das Geld zurück.'],
        'travel' => ['label' => 'Fahrtkostenabrechnung', 'prefix' => 'F', 'hint' => 'Fahrten mit dem eigenen Auto, der Bahn oder dem Nahverkehr.'],
        'purchase' => ['label' => 'Materialbestellung', 'prefix' => 'M', 'hint' => 'Etwas soll vor dem Kauf freigegeben werden.'],
    ];

    /** Nur diese Arten sind im Portal schon freigeschaltet. @return list<string> */
    public static function enabled(): array
    {
        return (array) config('portal.forms.enabled_types', ['expense']);
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function prefix(string $type): string
    {
        return self::TYPES[$type]['prefix'] ?? 'A';
    }
}
