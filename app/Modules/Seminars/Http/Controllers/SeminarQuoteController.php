<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Services\Quotes;
use App\Modules\Seminars\Support\QuoteCalculator;
use App\Modules\Seminars\Support\QuoteTexts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Kostenvoranschläge eines Seminars: berechnen (Vorschau), speichern und ansehen bzw. drucken. */
class SeminarQuoteController extends Controller
{
    public function __construct(private readonly Quotes $quotes) {}

    public function create(Seminar $seminar): View
    {
        $last = $seminar->quotes()->first();
        $values = $last !== null ? $last->inputs : QuoteCalculator::defaults($seminar->type);

        if ($last === null && $seminar->expected_participants !== null) {
            $values['group'] = $seminar->expected_participants;
        }

        $texts = $last !== null && filled($last->item_title) ? ['title' => $last->item_title, 'content' => (string) $last->item_content] : QuoteTexts::defaults($seminar->type, $values);

        return $this->form($seminar, $values + ['print_title' => $texts['title'], 'print_content' => $texts['content']], null);
    }

    public function store(Request $request, Seminar $seminar): View|RedirectResponse
    {
        $inputs = $request->validate(QuoteCalculator::rules() + ['print_title' => ['nullable', 'string', 'max:150'], 'print_content' => ['nullable', 'string', 'max:1000']], [
            'required' => 'Bitte füllen Sie dieses Feld aus.',
            'integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'numeric' => 'Bitte geben Sie eine Zahl ein.',
            'min' => 'Der Wert ist zu klein.',
            'max' => 'Der Wert ist zu groß.',
            'print_title.max' => 'Der Posten darf höchstens 150 Zeichen lang sein.',
            'print_content.max' => 'Der Inhalt darf höchstens 1000 Zeichen lang sein.',
        ]);

        $title = $inputs['print_title'] ?? null;
        $content = $inputs['print_content'] ?? null;
        unset($inputs['print_title'], $inputs['print_content']);

        if ($request->input('action') === 'preview') {
            return $this->form($seminar, $this->withCheckboxes($inputs) + ['print_title' => $title, 'print_content' => $content], $this->quotes->preview($inputs));
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $quote = $this->quotes->create($seminar, $inputs, $actor, $title, $content);

        return redirect()->route('seminars.quotes.show', [$seminar, $quote])->with('success', 'Der Kostenvoranschlag wurde gespeichert.');
    }

    public function show(Seminar $seminar, SeminarQuote $quote): View
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        return view('seminars.quote', ['seminar' => $seminar->load('partner', 'sessions'), 'quote' => $quote->load('priceList', 'createdBy.person')]);
    }

    /** Der Kostenvoranschlag als Schreiben im Briefformat des Vereins (zum Drucken oder als PDF speichern). */
    public function print(Seminar $seminar, SeminarQuote $quote): View
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        $quote->load('createdBy.person');
        $defaults = QuoteTexts::defaults($seminar->type, $quote->inputs);
        $partner = $seminar->partner;
        $country = strtoupper((string) $partner->country_code);

        return view('seminars.quote-print', [
            'seminar' => $seminar,
            'quote' => $quote,
            'partner' => $partner,
            'recipientLines' => array_values(array_filter([
                'Schulleitung',
                $partner->name,
                trim(($partner->street ?? '').' '.($partner->house_number ?? '')),
                trim(($partner->postal_code ?? '').' '.($partner->city ?? '')),
                $country === 'DE' || $country === '' ? 'Deutschland' : $country,
            ], fn (string $line): bool => $line !== '')),
            'itemTitle' => filled($quote->item_title) ? $quote->item_title : $defaults['title'],
            'itemLines' => QuoteTexts::lines(filled($quote->item_content) ? $quote->item_content : $defaults['content']),
            'author' => $quote->createdBy ? ($quote->createdBy->person?->fullName() ?: $quote->createdBy->email) : null,
            'letter' => config('portal.letter'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>|null  $preview
     */
    private function form(Seminar $seminar, array $values, ?array $preview): View
    {
        return view('seminars.quote-form', [
            'seminar' => $seminar,
            'values' => $values,
            'preview' => $preview,
            'labels' => QuoteCalculator::inputLabels($seminar->type),
            'version' => (int) $seminar->quotes()->max('version') + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    private function withCheckboxes(array $inputs): array
    {
        return $inputs + ['inside_abc' => false, 'without_club_fee' => false];
    }
}
