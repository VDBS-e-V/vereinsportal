<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Support\Money;
use App\Modules\Seminars\Support\QuoteCalculator;
use App\Modules\Seminars\Support\QuoteTexts;
use Illuminate\Support\Facades\DB;

/** Kostenvoranschläge berechnen und speichern (unveränderlich, jede Überarbeitung ist eine neue Fassung) sowie Preislisten pflegen. */
final class Quotes
{
    public function __construct(private readonly QuoteCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $inputs  validierte Eingaben (QuoteCalculator::rules)
     * @return array{inputs: array<string, mixed>, lines: list<array<string, mixed>>, total_cents: int, price_list: SeminarPriceList}
     */
    public function preview(array $inputs, ?SeminarPriceList $priceList = null): array
    {
        $priceList ??= SeminarPriceList::current();
        $inputs = $this->normalize($inputs);

        return ['inputs' => $inputs, 'price_list' => $priceList] + $this->calculator->calculate($inputs, $priceList->prices);
    }

    /** @param  array<string, mixed>  $inputs */
    public function create(Seminar $seminar, array $inputs, User $by, ?string $title = null, ?string $content = null): SeminarQuote
    {
        $result = $this->preview($inputs);
        $defaults = QuoteTexts::defaults($seminar->type, $result['inputs']);
        $title = filled($title) ? trim($title) : $defaults['title'];
        $content = filled($content) ? trim($content) : $defaults['content'];

        $quote = DB::transaction(function () use ($seminar, $result, $by, $title, $content): SeminarQuote {
            $version = (int) $seminar->quotes()->max('version') + 1;

            $quote = $seminar->quotes()->create([
                'version' => $version,
                'price_list_id' => $result['price_list']->id,
                'inputs' => $result['inputs'],
                'lines' => $result['lines'],
                'total_cents' => $result['total_cents'],
                'item_title' => $title,
                'item_content' => $content,
                'created_by_user_id' => $by->id,
            ]);

            $seminar->events()->create([
                'type' => SeminarEventType::QuoteCreated,
                'occurred_at' => now(),
                'actor_user_id' => $by->id,
                'actor_label' => AuditLog::label($by),
                'data' => ['fassung' => $version, 'summe' => Money::format($result['total_cents'])],
            ]);

            return $quote;
        });

        AuditLog::record(AuditEvent::SeminarQuoteCreated, $by, $seminar, ['fassung' => $quote->version]);

        return $quote;
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
