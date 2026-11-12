<?php

namespace App\Modules\Seminars\Support;

use App\Modules\Seminars\Enums\SeminarType;

/**
 * Kostenrechner für Kostenvoranschläge (Grundlage: Preisberechnungstabelle VDBS, Blatt „SV-Coaching“). Ein Rechner für alle Leistungsarten;
 * sie unterscheiden sich nur in den Voreinstellungen (defaults). Alle Beträge in Cent.
 *
 * Posten (Menge × Einzelpreis, je Tag mit der Zahl der Tage multipliziert):
 *  Honorar der Teamenden (Staffel nach Stunden pro Tag), Anfahrtspauschale je Teamende, Seminarleitung, Orgapauschale je Tag,
 *  Vereinspauschale (einmalig, entfällt bei „ohne Vereinspauschale“), Materialpauschale (Menge, bis 25 TN), Materialzuschlag
 *  je angefangene 5 TN ab 25, Material An- und Abfahrt, Pinnwand.
 */
final class QuoteCalculator
{
    public const GROUP_FREE = 25;

    public const GROUP_STEP = 5;

    /** @return array<string, int|float|bool> Voreinstellungen je Leistungsart */
    public static function defaults(SeminarType $type): array
    {
        $base = ['days' => 1, 'team' => 2, 'hours' => 6.0, 'leaders' => 0, 'group' => 25, 'inside_abc' => true, 'without_club_fee' => false, 'material_factor' => 1.0, 'material_trips' => 0, 'boards' => 0];

        return match ($type) {
            SeminarType::SvDays => ['days' => 3, 'team' => 3, 'hours' => 6.0, 'leaders' => 1, 'group' => 45, 'material_factor' => 1.0] + $base,
            SeminarType::Coaching => ['days' => 2, 'team' => 3, 'hours' => 6.0, 'leaders' => 0, 'group' => 30, 'material_factor' => 0.3] + $base,
            SeminarType::Election => ['days' => 1, 'team' => 2, 'hours' => 2.0, 'leaders' => 0, 'group' => 20, 'material_factor' => 0.3] + $base,
            SeminarType::GsvSupport => ['days' => 4, 'team' => 1, 'hours' => 3.0, 'leaders' => 0, 'group' => 20, 'material_factor' => 0.3] + $base,
        };
    }

    /** @return array<string, string> Bezeichnung der Eingaben */
    public static function inputLabels(SeminarType $type): array
    {
        return [
            'days' => 'Stückzahl ('.$type->unit().')',
            'team' => 'Teamende',
            'hours' => 'Stunden pro Tag (auch Vor- und Nachbereitung)',
            'leaders' => 'Seminarleitung (Anzahl)',
            'group' => 'Gruppengröße (Teilnehmende)',
            'material_factor' => 'Materialpauschale (Menge)',
            'material_trips' => 'Fahrten für Material',
            'boards' => 'Pinnwände',
        ];
    }

    /** @return array<string, array{0: string, 1: string}> Regeln und Meldungen der Eingaben */
    public static function rules(): array
    {
        return [
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'team' => ['required', 'integer', 'min:1', 'max:30'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:14'],
            'leaders' => ['required', 'integer', 'min:0', 'max:10'],
            'group' => ['required', 'integer', 'min:1', 'max:2000'],
            'inside_abc' => ['nullable', 'boolean'],
            'without_club_fee' => ['nullable', 'boolean'],
            'material_factor' => ['required', 'numeric', 'min:0', 'max:5'],
            'material_trips' => ['required', 'integer', 'min:0', 'max:20'],
            'boards' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, int>  $prices  Einzelpreise in Cent (Preisliste)
     * @return array{lines: list<array{key: string, label: string, quantity: float, unit: string, unit_cents: int, per_day: bool, total_cents: int}>, total_cents: int}
     */
    public function calculate(array $input, array $prices): array
    {
        $days = max(1, (int) $input['days']);
        $team = (int) $input['team'];
        $hours = (float) $input['hours'];
        $inside = (bool) ($input['inside_abc'] ?? false);
        $extraGroups = (int) ceil(max(0, (int) $input['group'] - self::GROUP_FREE) / self::GROUP_STEP);

        $honorarium = match (true) {
            $hours < 2.5 => $prices['honorarium_2_5'],
            $hours < 4.5 => $prices['honorarium_4_5'],
            $hours < 6 => $prices['honorarium_6'],
            default => $prices['honorarium_full'],
        };

        $rows = [
            ['honorarium', 'Honorar der Teamenden', $team, 'Teamende', $honorarium, true],
            ['trip', 'Anfahrtspauschale', $team, 'Teamende', $inside ? $prices['trip_inside'] : $prices['trip_outside'], true],
            ['leader', 'Satz Seminarleitung', (int) $input['leaders'], 'Seminarleitung', $hours < 4 ? $prices['leader_short'] : $prices['leader_long'], true],
            ['orga', 'Orgapauschale (pro Tag)', 1, 'pauschal', $prices['orga_day'], true],
            ['club_fee', 'Vereinspauschale (einmalig)', ($input['without_club_fee'] ?? false) ? 0 : 1, 'pauschal', $prices['club_fee'], false],
            ['material', 'Materialpauschale (bis 25 TN)', (float) $input['material_factor'], 'pauschal', $prices['material'], true],
            ['material_extra', 'Materialzuschlag Großgruppe (je 5 TN extra)', $extraGroups, 'je 5 TN', $prices['material_extra'], true],
            ['material_trip', 'Material An- und Abfahrt', (int) $input['material_trips'], 'Fahrten', $inside ? $prices['material_trip_inside'] : $prices['material_trip_outside'], true],
            ['board', 'Pinnwand (Ausleihe)', (int) $input['boards'], 'Stück', $prices['board'], true],
        ];

        $lines = [];
        $total = 0;

        foreach ($rows as [$key, $label, $quantity, $unit, $unitCents, $perDay]) {
            if ($quantity <= 0) {
                continue;
            }

            $lineTotal = (int) round($quantity * $unitCents * ($perDay ? $days : 1));
            $total += $lineTotal;
            $lines[] = ['key' => $key, 'label' => $label, 'quantity' => (float) $quantity, 'unit' => $unit, 'unit_cents' => (int) $unitCents, 'per_day' => $perDay, 'total_cents' => $lineTotal];
        }

        return ['lines' => $lines, 'total_cents' => $total];
    }
}
