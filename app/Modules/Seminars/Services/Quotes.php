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

    /**
     * Neuen Kostenvoranschlag speichern (Fassung 1).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function create(Partner $partner, ?Seminar $seminar, array $rows, User $by): SeminarQuote
    {
        $result = $this->preview($rows);

        $quote = DB::transaction(function () use ($partner, $seminar, $result, $by): SeminarQuote {
            $quote = SeminarQuote::query()->create([
                'partner_id' => $partner->id,
                'seminar_id' => $seminar?->id,
                'price_list_id' => $result['price_list']->id,
                'version' => 1,
                'positions' => $result['positions'],
                'total_cents' => $result['total_cents'],
                'created_by_user_id' => $by->id,
            ]);

            $this->snapshot($quote, $by, null);

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

    /**
     * Kostenvoranschlag ändern: Die Positionen werden mit der aktuellen Preisliste und den aktuellen Texten neu berechnet. Weicht das Ergebnis vom
     * bisherigen Stand ab, entsteht eine neue Fassung (mit Notiz); sonst bleibt alles, wie es ist.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return bool ob eine neue Fassung entstanden ist
     */
    public function update(SeminarQuote $quote, array $rows, User $by, ?string $comment = null): bool
    {
        $result = $this->preview($rows);

        // Lose Gleichheit: die Reihenfolge der Schlüssel spielt keine Rolle (MySQL ordnet JSON-Schlüssel neu)
        if ($result['positions'] == $quote->positions && $result['total_cents'] === $quote->total_cents) {
            return false;
        }

        $old = $quote->total_cents;

        DB::transaction(function () use ($quote, $result, $by, $comment): void {
            $quote->forceFill([
                'price_list_id' => $result['price_list']->id,
                'version' => $quote->version + 1,
                'positions' => $result['positions'],
                'total_cents' => $result['total_cents'],
            ])->save();

            $this->snapshot($quote, $by, $comment);

            $quote->seminar?->events()->create([
                'type' => SeminarEventType::QuoteUpdated,
                'occurred_at' => now(),
                'actor_user_id' => $by->id,
                'actor_label' => AuditLog::label($by),
                'comment' => filled($comment) ? trim($comment) : null,
                'data' => ['fassung' => $quote->version, 'summe' => Money::format($result['total_cents'])],
            ]);
        });

        AuditLog::record(AuditEvent::SeminarQuoteUpdated, $by, $quote->seminar ?? $quote->partner, ['fassung' => $quote->version, 'summe_alt' => Money::format($old), 'summe_neu' => Money::format($quote->total_cents)]);

        return true;
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

    /** Hält den aktuellen Stand als unveränderliche Fassung fest. */
    private function snapshot(SeminarQuote $quote, User $by, ?string $comment): void
    {
        $quote->versions()->create([
            'version' => $quote->version,
            'price_list_id' => $quote->price_list_id,
            'positions' => $quote->positions,
            'total_cents' => $quote->total_cents,
            'comment' => filled($comment) ? trim($comment) : null,
            'created_by_user_id' => $by->id,
        ]);
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
