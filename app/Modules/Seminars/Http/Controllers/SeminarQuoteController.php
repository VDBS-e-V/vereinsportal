<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Services\Quotes;
use App\Modules\Seminars\Support\QuoteCalculator;
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

        return $this->form($seminar, $values, null);
    }

    public function store(Request $request, Seminar $seminar): View|RedirectResponse
    {
        $inputs = $request->validate(QuoteCalculator::rules(), [
            'required' => 'Bitte füllen Sie dieses Feld aus.',
            'integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'numeric' => 'Bitte geben Sie eine Zahl ein.',
            'min' => 'Der Wert ist zu klein.',
            'max' => 'Der Wert ist zu groß.',
        ]);

        if ($request->input('action') === 'preview') {
            return $this->form($seminar, $this->withCheckboxes($inputs), $this->quotes->preview($inputs));
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $quote = $this->quotes->create($seminar, $inputs, $actor);

        return redirect()->route('seminars.quotes.show', [$seminar, $quote])->with('success', 'Der Kostenvoranschlag wurde gespeichert.');
    }

    public function show(Seminar $seminar, SeminarQuote $quote): View
    {
        abort_unless($quote->seminar_id === $seminar->id, 404);

        return view('seminars.quote', ['seminar' => $seminar->load('partner', 'sessions'), 'quote' => $quote->load('priceList', 'createdBy.person')]);
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
