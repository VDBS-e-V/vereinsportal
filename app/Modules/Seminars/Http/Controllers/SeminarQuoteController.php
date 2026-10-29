<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Services\Quotes;
use App\Modules\Seminars\Support\QuoteCalculator;
use App\Modules\Seminars\Support\QuoteTexts;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kostenvoranschläge (Bereich Koordination): je Schule mehrere Positionen, jede ein Produkt mit Stückzahl. Liste, Berechnen (Vorschau),
 * Speichern, Ansehen, als Brief drucken, Löschen und als Vorlage für einen neuen verwenden.
 */
class SeminarQuoteController extends Controller
{
    private const PER_PAGE = 20;

    private const MAX_POSITIONS = 20;

    public function __construct(private readonly Quotes $quotes) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $paginator = SeminarQuote::query()
            ->with(['partner', 'seminar'])
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->whereHas('partner', fn ($partner) => $partner->where('name', 'like', $like)->orWhere('city', 'like', $like));
            })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('seminars.quotes-index', [
            'quotes' => $paginator,
            'search' => $search !== '' ? $search : null,
            'active' => $search !== '' ? [['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])]] : [],
            'canManage' => Gate::allows('seminars.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $seminar = Seminar::query()->find((int) $request->query('seminar', 0));
        $copy = SeminarQuote::query()->find((int) $request->query('vorlage', 0));
        $partnerId = $seminar?->partner_id ?? $copy?->partner_id ?? ((int) $request->query('schule', 0) ?: null);

        if ($copy !== null) {
            $rows = collect($copy->positions)->map(fn (array $position): array => $position['inputs'] + ['product' => $position['product'], 'title' => $position['title'], 'content' => $position['content']])->all();
        } else {
            $type = $seminar?->type ?? SeminarType::SvDays;
            $inputs = QuoteCalculator::defaults($type);

            if ($seminar?->expected_participants !== null) {
                $inputs['group'] = $seminar->expected_participants;
            }

            $texts = QuoteTexts::defaults($type, $inputs);
            $rows = [$inputs + ['product' => $type->value, 'title' => $texts['title'], 'content' => $texts['content']]];
        }

        return $this->form(['partner_id' => $partnerId, 'seminar_id' => $seminar?->id, 'positions' => $rows], null);
    }

    public function store(Request $request): View|RedirectResponse
    {
        // Leere Zeilen (ohne Produkt) entfallen, die Zeilennummern bleiben erhalten
        $request->merge(['positions' => collect((array) $request->input('positions', []))->filter(fn ($row): bool => is_array($row) && filled($row['product'] ?? null))->all()]);

        $rules = [
            'partner_id' => ['required', 'integer', Rule::exists('partners', 'id')->where('type', PartnerType::School->value)],
            'seminar_id' => ['nullable', 'integer', Rule::exists('seminars', 'id')],
            'positions' => ['required', 'array', 'min:1', 'max:'.self::MAX_POSITIONS],
            'positions.*.product' => ['required', Rule::enum(SeminarType::class)],
            'positions.*.title' => ['nullable', 'string', 'max:150'],
            'positions.*.content' => ['nullable', 'string', 'max:1000'],
        ];

        foreach (QuoteCalculator::rules() as $key => $rule) {
            $rules['positions.*.'.$key] = $rule;
        }

        $data = $request->validate($rules, [
            'partner_id.required' => 'Bitte wählen Sie die Schule.',
            'partner_id.exists' => 'Bitte wählen Sie eine Schule aus der Liste.',
            'positions.required' => 'Bitte fügen Sie mindestens eine Position hinzu.',
            'positions.min' => 'Bitte fügen Sie mindestens eine Position hinzu.',
            'positions.max' => 'Ein Kostenvoranschlag kann höchstens '.self::MAX_POSITIONS.' Positionen haben.',
            'positions.*.product.required' => 'Bitte wählen Sie das Produkt.',
            'positions.*.product.enum' => 'Bitte wählen Sie ein bekanntes Produkt.',
            'positions.*.title.max' => 'Der Posten darf höchstens 150 Zeichen lang sein.',
            'positions.*.content.max' => 'Der Inhalt darf höchstens 1000 Zeichen lang sein.',
            'required' => 'Bitte füllen Sie dieses Feld aus.',
            'integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'numeric' => 'Bitte geben Sie eine Zahl ein.',
            'min' => 'Der Wert ist zu klein.',
            'max' => 'Der Wert ist zu groß.',
        ]);

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);
        $seminar = filled($data['seminar_id'] ?? null) ? Seminar::query()->findOrFail((int) $data['seminar_id']) : null;

        if ($seminar !== null && $seminar->partner_id !== $partner->id) {
            throw ValidationException::withMessages(['seminar_id' => 'Das Seminar gehört zu einer anderen Schule.']);
        }

        if ($request->input('action') === 'preview') {
            $rows = $data['positions'];

            return $this->form(['partner_id' => $partner->id, 'seminar_id' => $seminar?->id, 'positions' => $this->withCheckboxes($rows)], $this->quotes->preview($rows));
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $quote = $this->quotes->create($partner, $seminar, $data['positions'], $actor);

        return redirect()->route('seminar-quotes.show', $quote)->with('success', 'Der Kostenvoranschlag wurde gespeichert.');
    }

    public function show(SeminarQuote $quote): View
    {
        $quote->load(['partner', 'seminar', 'priceList', 'createdBy.person']);

        return view('seminars.quote', ['quote' => $quote, 'canManage' => Gate::allows('seminars.manage')]);
    }

    public function destroy(Request $request, SeminarQuote $quote): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $seminar = $quote->seminar;

        if ($seminar !== null && $seminar->status === SeminarStatus::Confirmed && $seminar->quotes()->count() === 1) {
            return redirect()->route('seminar-quotes.show', $quote)->with('warning', 'Dieses Seminar ist bestätigt und hat nur diesen Kostenvoranschlag. Ändern Sie zuerst den Status des Seminars.');
        }

        $this->quotes->delete($quote, $actor);

        return redirect()->route('seminar-quotes.index')->with('success', 'Der Kostenvoranschlag wurde gelöscht.');
    }

    /** Der Kostenvoranschlag als Schreiben im Briefformat des Vereins (zum Drucken oder als PDF speichern). */
    public function print(SeminarQuote $quote): View
    {
        $quote->load(['partner', 'createdBy.person']);
        $partner = $quote->partner;
        $country = strtoupper((string) $partner->country_code);

        return view('seminars.quote-print', [
            'quote' => $quote,
            'items' => collect($quote->positions)->map(fn (array $position): array => [
                'title' => $position['title'],
                'lines' => QuoteTexts::lines($position['content']),
                'total_cents' => $position['total_cents'],
            ])->all(),
            'recipientLines' => array_values(array_filter([
                'Schulleitung',
                $partner->name,
                trim(($partner->street ?? '').' '.($partner->house_number ?? '')),
                trim(($partner->postal_code ?? '').' '.($partner->city ?? '')),
                $country === 'DE' || $country === '' ? 'Deutschland' : $country,
            ], fn (string $line): bool => $line !== '')),
            'author' => $quote->createdBy ? ($quote->createdBy->person?->fullName() ?: $quote->createdBy->email) : null,
            'letter' => config('portal.letter'),
        ]);
    }

    /**
     * @param  array{partner_id: ?int, seminar_id: ?int, positions: array<int|string, array<string, mixed>>}  $values
     * @param  array<string, mixed>|null  $preview
     */
    private function form(array $values, ?array $preview): View
    {
        $defaults = [];
        $texts = [];

        foreach (SeminarType::cases() as $type) {
            $defaults[$type->value] = QuoteCalculator::defaults($type);
            $texts[$type->value] = QuoteTexts::defaults($type, $defaults[$type->value]);
        }

        $keys = array_keys($values['positions']);
        $next = $keys === [] ? 0 : max(array_map('intval', $keys)) + 1;
        $rows = $values['positions'] + [$next => []];

        return view('seminars.quote-form', [
            'values' => $values,
            'rows' => $rows,
            'nextIndex' => $next + 1,
            'preview' => $preview,
            'products' => SeminarType::options(),
            'defaults' => $defaults,
            'texts' => $texts,
            'units' => collect(SeminarType::cases())->mapWithKeys(fn (SeminarType $type): array => [$type->value => $type->unit()])->all(),
            'partnerOptions' => Partner::query()->where('type', PartnerType::School->value)->orderBy('name')->get()
                ->mapWithKeys(fn (Partner $partner): array => [$partner->id => $partner->name.($partner->city ? ' ('.$partner->city.')' : '')])->all(),
            'seminarOptions' => Seminar::query()->with('partner')->whereNot('status', SeminarStatus::Cancelled->value)->orderByDesc('id')->limit(300)->get()
                ->mapWithKeys(fn (Seminar $seminar): array => [$seminar->id => $seminar->title.' – '.$seminar->partner->name])->all(),
        ]);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return array<int|string, array<string, mixed>>
     */
    private function withCheckboxes(array $rows): array
    {
        return array_map(fn (array $row): array => $row + ['inside_abc' => false, 'without_club_fee' => false], $rows);
    }
}
