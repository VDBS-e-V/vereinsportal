<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Models\SeminarProductText;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Support\Money;
use App\Modules\Seminars\Support\QuoteCalculator;
use App\Modules\Seminars\Support\QuoteTexts;
use Illuminate\Support\Facades\DB;

/**
 * Kostenvoranschläge: je Schule mehrere Positionen (Produkt mit Stückzahl), jede mit eigener Berechnung (QuoteCalculator, die Vereinspauschale
 * je Position). Gespeichert wird unveränderlich mit dem Preisstand von damals. Dazu die Preislisten.
 */
final class Quotes
{
    public function __construct(private readonly QuoteCalculator $calculator) {}

    /**
     * @param  list<array<string, mixed>>  $rows  Positionen aus dem Formular (product, Eingaben des Rechners, extra = ergänzende Beschreibung)
     * @return array{positions: list<array<string, mixed>>, total_cents: int, price_list: SeminarPriceList}
     */
    public function preview(array $rows, ?SeminarPriceList $priceList = null): array
    {
        $priceList ??= SeminarPriceList::current();
        $positions = [];
        $total = 0;

        foreach (array_values($rows) as $row) {
            $type = SeminarType::from((string) $row['product']);
            $inputs = $this->normalize($row);
            $result = $this->calculator->calculate($inputs, $priceList->prices);
            $defaults = QuoteTexts::defaults($type, $inputs);

            $positions[] = [
                'product' => $type->value,
                'inputs' => $inputs,
                'lines' => $result['lines'],
                'total_cents' => $result['total_cents'],
                'title' => $defaults['title'],
                'content' => $defaults['content'],
                'extra' => trim((string) ($row['extra'] ?? '')),
            ];
            $total += $result['total_cents'];
        }

        return ['positions' => $positions, 'total_cents' => $total, 'price_list' => $priceList];
    }

    /** @param  list<array<string, mixed>>  $rows */
    public function create(Partner $partner, ?Seminar $seminar, array $rows, User $by): SeminarQuote
    {
        $result = $this->preview($rows);

        $quote = DB::transaction(function () use ($partner, $seminar, $result, $by): SeminarQuote {
            $quote = SeminarQuote::query()->create([
                'partner_id' => $partner->id,
                'seminar_id' => $seminar?->id,
                'price_list_id' => $result['price_list']->id,
                'positions' => $result['positions'],
                'total_cents' => $result['total_cents'],
                'created_by_user_id' => $by->id,
            ]);

            $seminar?->events()->create([
                'type' => SeminarEventType::QuoteCreated,
                'occurred_at' => now(),
                'actor_user_id' => $by->id,
                'actor_label' => AuditLog::label($by),
                'data' => ['summe' => Money::format($result['total_cents']), 'positionen' => count($result['positions'])],
            ]);

            return $quote;
        });

        AuditLog::record(AuditEvent::SeminarQuoteCreated, $by, $seminar ?? $partner, ['summe' => Money::format($result['total_cents']), 'positionen' => count($result['positions'])]);

        return $quote;
    }

    public function delete(SeminarQuote $quote, User $by): void
    {
        AuditLog::record(AuditEvent::SeminarQuoteDeleted, $by, $quote->seminar ?? $quote->partner, ['summe' => Money::format($quote->total_cents)]);

        $quote->delete();
    }

    /**
     * Globale Texte (Titel und Standardbeschreibung) der Produkte speichern.
     *
     * @param  array<string, array{title: string, content: string}>  $texts  je Produkt
     * @return list<string> die Produkte, deren Text sich geändert hat
     */
    public function saveTexts(array $texts, User $by): array
    {
        $changed = [];

        foreach ($texts as $product => $text) {
            $current = QuoteTexts::template(SeminarType::from($product));

            if ($current['title'] === $text['title'] && $current['content'] === $text['content']) {
                continue;
            }

            SeminarProductText::query()->updateOrCreate(['product' => $product], ['title' => $text['title'], 'content' => $text['content'], 'updated_by_user_id' => $by->id]);
            $changed[] = $product;
        }

        if ($changed !== []) {
            AuditLog::record(AuditEvent::SeminarTextsChanged, $by, null, ['produkte' => $changed]);
        }

        return $changed;
    }

    /**
     * Neue Fassung der Preisliste anlegen (die alte bleibt für bestehende Kostenvoranschläge erhalten).
     *
     * @param  array<string, int>  $prices  Cent je Posten
     */
    public function createPriceList(string $label, string $validFrom, array $prices, User $by): SeminarPriceList
    {
        $list = SeminarPriceList::query()->create(['label' => $label, 'valid_from' => $validFrom, 'prices' => $prices, 'created_by_user_id' => $by->id]);

        AuditLog::record(AuditEvent::SeminarPricesChanged, $by, null, ['bezeichnung' => $label, 'gueltig_ab' => $validFrom]);

        return $list;
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    private function normalize(array $inputs): array
    {
        return [
            'days' => (int) $inputs['days'],
            'team' => (int) $inputs['team'],
            'hours' => (float) $inputs['hours'],
            'leaders' => (int) $inputs['leaders'],
            'group' => (int) $inputs['group'],
            'inside_abc' => (bool) ($inputs['inside_abc'] ?? false),
            'without_club_fee' => (bool) ($inputs['without_club_fee'] ?? false),
            'material_factor' => (float) $inputs['material_factor'],
            'material_trips' => (int) $inputs['material_trips'],
            'boards' => (int) $inputs['boards'],
        ];
    }
}
