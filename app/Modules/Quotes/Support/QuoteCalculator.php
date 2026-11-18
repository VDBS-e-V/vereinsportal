<?php

namespace App\Modules\Quotes\Support;

/** Rechnet Positionen eines Kostenvoranschlags in Cent: Zeilensumme, Netto, Umsatzsteuer, Gesamt. */
final class QuoteCalculator
{
    public static function lineTotal(float|string $quantity, int $unitPriceCents): int
    {
        return (int) round((float) $quantity * $unitPriceCents);
    }

    /**
     * @param  iterable<array{quantity: float|string, unit_price_cents: int}>  $lines
     * @return array{net: int, vat: int, total: int}
     */
    public static function totals(iterable $lines, float|string $vatPercent): array
    {
        $net = 0;

        foreach ($lines as $line) {
            $net += self::lineTotal($line['quantity'], $line['unit_price_cents']);
        }

        $vat = (int) round($net * (float) $vatPercent / 100);

        return ['net' => $net, 'vat' => $vat, 'total' => $net + $vat];
    }

    /** Menge aus einer Eingabe („1,5“ oder „2“), null bei ungültiger Eingabe. */
    public static function parseQuantity(string $input): ?float
    {
        $input = str_replace(',', '.', trim($input));

        if (preg_match('/^\d{1,5}(\.\d{1,2})?$/', $input) !== 1) {
            return null;
        }

        $value = (float) $input;

        return $value > 0 ? $value : null;
    }
}
