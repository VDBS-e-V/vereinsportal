<?php

use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Support\Money;
use App\Modules\Seminars\Support\QuoteCalculator;

/** Preisstand der Preisberechnungstabelle (Oktober 2025). */
function qcPrices(): array
{
    return SeminarPriceList::current()->prices;
}

function qcLine(array $result, string $key): ?array
{
    return collect($result['lines'])->firstWhere('key', $key);
}

it('calculates SV-Tage with the defaults like the spreadsheet', function () {
    $result = (new QuoteCalculator)->calculate(QuoteCalculator::defaults(SeminarType::SvDays), qcPrices());

    // 3 Teamende × 200 € × 3 Tage, Anfahrt 3 × 10 € × 3, SL 50 € × 3, Orga 20 € × 3, Verein 65 €, Material 32 € × 3, Zuschlag 4 × 4 € × 3
    expect(qcLine($result, 'honorarium')['total_cents'])->toBe(180000)
        ->and(qcLine($result, 'trip')['total_cents'])->toBe(9000)
        ->and(qcLine($result, 'leader')['total_cents'])->toBe(15000)
        ->and(qcLine($result, 'orga')['total_cents'])->toBe(6000)
        ->and(qcLine($result, 'club_fee')['total_cents'])->toBe(6500)
        ->and(qcLine($result, 'material')['total_cents'])->toBe(9600)
        ->and(qcLine($result, 'material_extra')['total_cents'])->toBe(4800)
        ->and($result['total_cents'])->toBe(230900);
});

it('calculates SV-Coaching with the defaults like the spreadsheet', function () {
    $result = (new QuoteCalculator)->calculate(QuoteCalculator::defaults(SeminarType::Coaching), qcPrices());

    // 1200 + 60 + 40 + 65 + 19,20 + 8 = 1392,20 €, keine Seminarleitung
    expect($result['total_cents'])->toBe(139220)->and(qcLine($result, 'leader'))->toBeNull();
});

it('calculates SV-Wahl with the defaults like the spreadsheet', function () {
    $result = (new QuoteCalculator)->calculate(QuoteCalculator::defaults(SeminarType::Election), qcPrices());

    // 2 × 100 € + 2 × 10 € + 20 € + 65 € + 9,60 € = 314,60 €, kein Materialzuschlag (20 TN)
    expect($result['total_cents'])->toBe(31460)->and(qcLine($result, 'material_extra'))->toBeNull();
});

it('calculates the GSV support with its defaults', function () {
    $result = (new QuoteCalculator)->calculate(QuoteCalculator::defaults(SeminarType::GsvSupport), qcPrices());

    // 4 Sitzungen: 135 € × 4 + 10 € × 4 + 20 € × 4 + 65 € + 9,60 € × 4 = 763,40 €
    expect($result['total_cents'])->toBe(76340)->and(SeminarType::GsvSupport->unit())->toBe('Sitzungen')->and(SeminarType::SvDays->unit())->toBe('Tage');
});

it('uses the honorarium scale by hours per day for every service', function () {
    $calc = new QuoteCalculator;
    $base = QuoteCalculator::defaults(SeminarType::SvDays);
    $rate = fn (float $hours): int => qcLine($calc->calculate(['hours' => $hours] + $base, qcPrices()), 'honorarium')['unit_cents'];

    expect($rate(2.0))->toBe(10000)->and($rate(2.5))->toBe(13500)->and($rate(4.0))->toBe(13500)->and($rate(4.5))->toBe(17000)->and($rate(5.5))->toBe(17000)->and($rate(6.0))->toBe(20000)->and($rate(8.0))->toBe(20000);
});

it('charges the leader supplement by length and the material extra per started five participants', function () {
    $calc = new QuoteCalculator;
    $base = ['leaders' => 1] + QuoteCalculator::defaults(SeminarType::SvDays);

    $short = $calc->calculate(['hours' => 3.0] + $base, qcPrices());
    $long = $calc->calculate(['hours' => 4.0] + $base, qcPrices());
    $group = $calc->calculate(['group' => 27] + $base, qcPrices());
    $small = $calc->calculate(['group' => 10] + $base, qcPrices());

    expect(qcLine($short, 'leader')['unit_cents'])->toBe(3000)
        ->and(qcLine($long, 'leader')['unit_cents'])->toBe(5000)
        ->and(qcLine($group, 'material_extra')['quantity'])->toBe(1.0)
        ->and(qcLine($small, 'material_extra'))->toBeNull();
});

it('uses the larger travel prices outside ABC and drops the club fee on request', function () {
    $calc = new QuoteCalculator;
    $base = ['material_trips' => 1] + QuoteCalculator::defaults(SeminarType::Coaching);

    $inside = $calc->calculate($base, qcPrices());
    $outside = $calc->calculate(['inside_abc' => false, 'without_club_fee' => true] + $base, qcPrices());

    expect(qcLine($inside, 'trip')['unit_cents'])->toBe(1000)
        ->and(qcLine($inside, 'material_trip')['unit_cents'])->toBe(3500)
        ->and(qcLine($outside, 'trip')['unit_cents'])->toBe(4500)
        ->and(qcLine($outside, 'material_trip')['unit_cents'])->toBe(10500)
        ->and(qcLine($outside, 'club_fee'))->toBeNull()
        ->and(qcLine($inside, 'club_fee')['total_cents'])->toBe(6500);
});

it('multiplies day based items by the days and the club fee only once', function () {
    $one = (new QuoteCalculator)->calculate(['days' => 1] + QuoteCalculator::defaults(SeminarType::SvDays), qcPrices());
    $four = (new QuoteCalculator)->calculate(['days' => 4] + QuoteCalculator::defaults(SeminarType::SvDays), qcPrices());

    expect(qcLine($four, 'orga')['total_cents'])->toBe(4 * qcLine($one, 'orga')['total_cents'])
        ->and(qcLine($four, 'club_fee')['total_cents'])->toBe(qcLine($one, 'club_fee')['total_cents']);
});

it('formats and reads amounts', function () {
    expect(Money::format(139220))->toBe('1.392,20 €')
        ->and(Money::parseEuro('32,50'))->toBe(3250)
        ->and(Money::parseEuro('32.5'))->toBe(3250)
        ->and(Money::parseEuro('65'))->toBe(6500)
        ->and(Money::parseEuro('1.000,00'))->toBeNull()
        ->and(Money::parseEuro('abc'))->toBeNull()
        ->and(Money::toInput(3250))->toBe('32,50');
});
