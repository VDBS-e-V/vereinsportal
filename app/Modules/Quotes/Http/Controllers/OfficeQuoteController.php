<?php

namespace App\Modules\Quotes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\QuotePriceItem;
use App\Modules\Quotes\Models\SeminarQuote;
use App\Modules\Quotes\Services\QuotePdf;
use App\Modules\Quotes\Services\Quotes;
use App\Modules\Quotes\Support\QuoteCalculator;
use App\Modules\Seminars\Models\Seminar;
use App\Support\Money;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** Geschäftsstelle: Kostenvoranschlag anlegen, bearbeiten, senden; Freigabe durch Vorstand oder Koordination; PDF. */
class OfficeQuoteController extends Controller
{
    public const LINE_ROWS = 10;

    public function __construct(private readonly Quotes $quotes, private readonly QuotePdf $pdf, private readonly SchoolAccounts $accounts) {}

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        try {
            $quote = $this->quotes->createDraft($seminar, $this->user($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.office.show', $seminar)->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('quotes.office.edit', [$seminar, $quote])->with('success', 'Version '.$quote->version.' des Kostenvoranschlags ist angelegt.');
    }

    public function edit(Seminar $seminar, SeminarQuote $quote): View
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        return view('quotes.edit', [
            'seminar' => $seminar->load('partner'),
            'quote' => $quote->load('lines'),
            'items' => QuotePriceItem::query()->where('active', true)->ordered()->get(),
            'rows' => self::LINE_ROWS,
        ]);
    }

    public function update(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        $data = $request->validate([
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'outro' => ['nullable', 'string', 'max:5000'],
            'lines' => ['nullable', 'array', 'max:'.self::LINE_ROWS],
        ], ['vat_percent.numeric' => 'Bitte geben Sie den Steuersatz als Zahl an, zum Beispiel 0 oder 19.']);

        $lines = $this->parseLines((array) ($data['lines'] ?? []));

        try {
            $this->quotes->save($quote, $lines, (float) ($data['vat_percent'] ?? 0), $data['intro'] ?? null, $data['outro'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('quotes.office.edit', [$seminar, $quote])->with('success', 'Der Entwurf ist gespeichert. Eine frühere Freigabe gilt dafür nicht mehr.');
    }

    /** Position aus der Preisliste an den Entwurf anhängen. */
    public function addItem(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        $data = $request->validate(['item' => ['required', 'integer', 'exists:quote_price_items,id']], ['item.required' => 'Bitte wählen Sie eine Leistung aus der Preisliste.']);
        $item = QuotePriceItem::query()->findOrFail($data['item']);

        $lines = $quote->lines->map(fn ($line): array => ['description' => $line->description, 'quantity' => (float) $line->quantity, 'unit' => $line->unit, 'unit_price_cents' => $line->unit_price_cents])->all();

        if (count($lines) >= self::LINE_ROWS) {
            return back()->withErrors(['quote' => 'Ein Kostenvoranschlag hat höchstens '.self::LINE_ROWS.' Positionen.']);
        }

        $lines[] = ['description' => $item->name, 'quantity' => 1.0, 'unit' => $item->unit, 'unit_price_cents' => $item->unit_price_cents];

        try {
            $this->quotes->save($quote, $lines, (float) $quote->vat_percent, $quote->intro, $quote->outro);
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('quotes.office.edit', [$seminar, $quote])->with('success', '„'.$item->name.'“ ist als Position eingetragen.');
    }

    public function send(Request $request, Seminar $seminar, SeminarQuote $quote): RedirectResponse
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        try {
            $this->quotes->send($quote, $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return redirect()->route('seminars.office.show', $seminar)->with('success', 'Der Kostenvoranschlag ist an die Schule gesendet.');
    }

    /** Seite für Freigebende: Entwürfe, die noch nicht intern freigegeben sind. */
    public function approvals(): View
    {
        return view('quotes.approvals', ['quotes' => SeminarQuote::query()->where('status', QuoteStatus::Draft->value)->with('seminar.partner')->orderBy('id')->get()]);
    }

    public function approve(Request $request, SeminarQuote $quote): RedirectResponse
    {
        try {
            $this->quotes->approve($quote, $this->user($request));
        } catch (DomainException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return back()->with('success', 'Der Kostenvoranschlag ist intern freigegeben.');
    }

    public function pdf(Request $request, SeminarQuote $quote): Response
    {
        $user = $this->user($request);
        $allowed = $user->can('seminars.review') || $user->can('seminars.quote_approve');

        if (! $allowed && $user->can('school.area')) {
            $account = $this->accounts->forUser($user);
            $allowed = $account !== null && $quote->seminar->partner_id === $account->partner_id && in_array($quote->status, [QuoteStatus::Sent, QuoteStatus::Accepted, QuoteStatus::Declined, QuoteStatus::Superseded], true) && $quote->sent_at !== null;
        }

        abort_unless($allowed, 404);

        return response($this->pdf->render($quote), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$this->pdf->filename($quote).'"']);
    }

    /**
     * Zeilen aus dem Formular: leere Zeilen entfallen; eine Zeile braucht Text, Menge und Preis.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{description: string, quantity: float, unit: ?string, unit_price_cents: int}>
     */
    private function parseLines(array $rows): array
    {
        $lines = [];

        foreach ($rows as $index => $row) {
            $row = (array) $row;
            $description = trim((string) ($row['description'] ?? ''));
            $quantityInput = trim((string) ($row['quantity'] ?? ''));
            $priceInput = trim((string) ($row['price'] ?? ''));
            $unit = trim((string) ($row['unit'] ?? ''));

            if ($description === '' && $quantityInput === '' && $priceInput === '') {
                continue;
            }

            $quantity = QuoteCalculator::parseQuantity($quantityInput === '' ? '1' : $quantityInput);
            $price = Money::parseEuro($priceInput === '' ? '0' : $priceInput);

            if ($description === '' || mb_strlen($description) > 300) {
                throw ValidationException::withMessages(['lines.'.$index.'.description' => 'Bitte beschreiben Sie die Position (höchstens 300 Zeichen).']);
            }

            if ($quantity === null) {
                throw ValidationException::withMessages(['lines.'.$index.'.quantity' => 'Bitte geben Sie die Menge als Zahl größer als 0 an.']);
            }

            if ($price === null) {
                throw ValidationException::withMessages(['lines.'.$index.'.price' => 'Bitte geben Sie den Einzelpreis in Euro an, zum Beispiel 250,00.']);
            }

            $lines[] = ['description' => $description, 'quantity' => $quantity, 'unit' => $unit === '' ? null : mb_substr($unit, 0, 30), 'unit_price_cents' => $price];
        }

        return $lines;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
