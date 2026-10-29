<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\SeminarPriceList;
use App\Modules\Seminars\Services\Quotes;
use App\Modules\Seminars\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Preisliste der Seminare: ansehen und als neue Fassung ändern (alte Fassungen bleiben für bestehende Kostenvoranschläge erhalten). */
class SeminarPriceController extends Controller
{
    public function __construct(private readonly Quotes $quotes) {}

    public function index(): View
    {
        return view('seminars.prices', [
            'current' => SeminarPriceList::current(),
            'lists' => SeminarPriceList::query()->orderByDesc('valid_from')->orderByDesc('id')->get(),
            'items' => SeminarPriceList::ITEMS,
            'canManage' => Gate::allows('seminars.prices'),
        ]);
    }

    public function create(): View
    {
        $current = SeminarPriceList::current();

        return view('seminars.prices-form', [
            'current' => $current,
            'items' => SeminarPriceList::ITEMS,
            'values' => collect($current->prices)->map(fn (int $cents): string => Money::toInput($cents))->all() + ['label' => '', 'valid_from' => now()->toDateString()],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'label' => ['required', 'string', 'max:120'],
            'valid_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before_or_equal:'.now()->addYears(2)->toDateString()],
        ];

        foreach (array_keys(SeminarPriceList::ITEMS) as $key) {
            $rules['price_'.$key] = ['required', 'string', 'max:12'];
        }

        $data = $request->validate($rules, [
            'label.required' => 'Bitte geben Sie eine Bezeichnung ein, z. B. „Preisstand Januar 2027“.',
            'valid_from.required' => 'Bitte geben Sie an, ab wann die Preise gelten.',
            'valid_from.date_format' => 'Bitte geben Sie ein gültiges Datum an.',
            'valid_from.after_or_equal' => 'Bitte prüfen Sie das Datum.',
            'valid_from.before_or_equal' => 'Bitte prüfen Sie das Datum.',
            'required' => 'Bitte geben Sie den Preis ein.',
        ]);

        $prices = [];
        $errors = [];

        foreach (array_keys(SeminarPriceList::ITEMS) as $key) {
            $cents = Money::parseEuro($data['price_'.$key]);

            if ($cents === null) {
                $errors['price_'.$key] = 'Bitte geben Sie den Preis in Euro an, z. B. 32,50.';

                continue;
            }

            $prices[$key] = $cents;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $this->quotes->createPriceList(trim($data['label']), $data['valid_from'], $prices, $actor);

        return redirect()->route('seminar-prices.index')->with('success', 'Die neue Preisliste wurde gespeichert.');
    }
}
