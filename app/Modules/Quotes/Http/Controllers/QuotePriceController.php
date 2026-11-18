<?php

namespace App\Modules\Quotes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Quotes\Models\QuotePriceItem;
use App\Modules\Quotes\Models\QuoteText;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Preisliste und Textbausteine für Kostenvoranschläge (Geschäftsstelle). Positionen in Kostenvoranschlägen sind Kopien und ändern sich nicht mit. */
class QuotePriceController extends Controller
{
    public const ROWS = 15;

    public function edit(): View
    {
        return view('quotes.prices', [
            'items' => QuotePriceItem::query()->ordered()->get()->values(),
            'rows' => self::ROWS,
            'texts' => collect(QuoteText::KEYS)->map(fn (string $label, string $key): string => QuoteText::body($key))->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['nullable', 'array', 'max:'.self::ROWS],
            'texts' => ['nullable', 'array'],
            'texts.intro' => ['nullable', 'string', 'max:5000'],
            'texts.outro' => ['nullable', 'string', 'max:5000'],
        ]);

        $rows = [];

        foreach ((array) ($data['items'] ?? []) as $index => $row) {
            $row = (array) $row;
            $name = trim((string) ($row['name'] ?? ''));
            $priceInput = trim((string) ($row['price'] ?? ''));

            if ($name === '' && $priceInput === '') {
                continue;
            }

            $price = Money::parseEuro($priceInput);

            if ($name === '' || mb_strlen($name) > 150) {
                throw ValidationException::withMessages(['items.'.$index.'.name' => 'Bitte benennen Sie die Leistung (höchstens 150 Zeichen).']);
            }

            if ($price === null) {
                throw ValidationException::withMessages(['items.'.$index.'.price' => 'Bitte geben Sie den Preis in Euro an, zum Beispiel 250,00.']);
            }

            $unit = trim((string) ($row['unit'] ?? ''));
            $rows[] = ['name' => $name, 'unit' => $unit === '' ? null : mb_substr($unit, 0, 30), 'unit_price_cents' => $price, 'active' => (string) ($row['active'] ?? '1') !== '0', 'sort_order' => count($rows) + 1, 'created_at' => now(), 'updated_at' => now()];
        }

        DB::transaction(function () use ($rows, $data): void {
            QuotePriceItem::query()->delete();
            QuotePriceItem::query()->insert($rows);

            foreach (array_keys(QuoteText::KEYS) as $key) {
                QuoteText::query()->updateOrCreate(['key' => $key], ['body' => trim((string) ($data['texts'][$key] ?? ''))]);
            }
        });

        AuditLog::record(AuditEvent::QuotePricesSaved, $this->user(), null, ['anzahl' => count($rows)]);

        return redirect()->route('quotes.office.prices')->with('success', 'Preisliste und Textbausteine sind gespeichert.');
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
