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
            $rows = collect($copy->positions)->map(fn (array $position): array => $position['inputs'] + ['product' => $position['product'], 'extra' => $position['extra'] ?? ''])->all();
        } else {
            $type = $seminar?->type ?? SeminarType::SvDays;
            $inputs = QuoteCalculator::defaults($type);

            if ($seminar?->expected_participants !== null) {
                $inputs['group'] = $seminar->expected_participants;
            }

            $rows = [$inputs + ['product' => $type->value, 'extra' => '']];
        }

        return $this->form(['partner_id' => $partnerId, 'seminar_id' => $seminar?->id, 'positions' => $rows], null);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $data = $this->validatePositions($request, [
            'partner_id' => ['required', 'integer', Rule::exists('partners', 'id')->where('type', PartnerType::School->value)],
            'seminar_id' => ['nullable', 'integer', Rule::exists('seminars', 'id')],
        ], [
            'partner_id.required' => 'Bitte wählen Sie die Schule.',
            'partner_id.exists' => 'Bitte wählen Sie eine Schule aus der Liste.',
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

    /** Bestehenden Kostenvoranschlag ändern (mit ?fassung=N als Ausgangspunkt eine frühere Fassung). */
    public function edit(Request $request, SeminarQuote $quote): View
    {
        $quote->load(['partner', 'seminar']);
        $source = $this->versionData($quote, (int) $request->query('fassung', 0) ?: null);

        $rows = collect($source['positions'])->map(fn (array $position): array => $position['inputs'] + ['product' => $position['product'], 'extra' => $position['extra'] ?? ''])->all();

        return $this->form(['partner_id' => $quote->partner_id, 'seminar_id' => $quote->seminar_id, 'positions' => $rows], null, $quote, $source['version'] !== $quote->version ? $source['version'] : null);
    }

    public function update(Request $request, SeminarQuote $quote): View|RedirectResponse
    {
        $data = $this->validatePositions($request, ['change_comment' => ['nullable', 'string', 'max:500']], ['change_comment.max' => 'Die Notiz darf höchstens 500 Zeichen lang sein.']);

        if ($request->input('action') === 'preview') {
            $rows = $data['positions'];

            return $this->form(['partner_id' => $quote->partner_id, 'seminar_id' => $quote->seminar_id, 'positions' => $this->withCheckboxes($rows), 'change_comment' => $data['change_comment'] ?? null], $this->quotes->preview($rows), $quote->load(['partner', 'seminar']));
        }

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $changed = $this->quotes->update($quote, $data['positions'], $actor, $data['change_comment'] ?? null);

        return redirect()->route('seminar-quotes.show', $quote)->with('success', $changed ? 'Der Kostenvoranschlag wurde geändert (Fassung '.$quote->version.').' : 'Es gab keine Änderungen.');
    }

    public function show(Request $request, SeminarQuote $quote): View
    {
        $quote->load(['partner', 'seminar', 'versions.createdBy.person', 'versions.priceList']);
        $data = $this->versionData($quote, (int) $request->query('fassung', 0) ?: null);

        return view('seminars.quote', ['quote' => $quote, 'data' => $data, 'canManage' => Gate::allows('seminars.manage')]);
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
    public function print(Request $request, SeminarQuote $quote): View
    {
        $quote->load('partner');
        $data = $this->versionData($quote, (int) $request->query('fassung', 0) ?: null);
        $partner = $quote->partner;
        $country = strtoupper((string) $partner->country_code);

        return view('seminars.quote-print', [
            'quote' => $quote,
            'data' => $data,
            'items' => collect($data['positions'])->map(fn (array $position): array => [
                'title' => $position['title'],
                'lines' => array_merge(QuoteTexts::lines($position['content']), QuoteTexts::lines($position['extra'] ?? '')),
                'total_cents' => $position['total_cents'],
            ])->all(),
            'recipientLines' => array_values(array_filter([
                'Schulleitung',
                $partner->name,
                trim(($partner->street ?? '').' '.($partner->house_number ?? '')),
                trim(($partner->postal_code ?? '').' '.($partner->city ?? '')),
                $country === 'DE' || $country === '' ? 'Deutschland' : $country,
            ], fn (string $line): bool => $line !== '')),
            'author' => $data['author'],
            'letter' => config('portal.letter'),
        ]);
    }

    /**
     * @param  array{partner_id: ?int, seminar_id: ?int, positions: array<int|string, array<string, mixed>>, change_comment?: ?string}  $values
     * @param  array<string, mixed>|null  $preview
     */
    private function form(array $values, ?array $preview, ?SeminarQuote $quote = null, ?int $fromVersion = null): View
    {
        $defaults = [];

        foreach (SeminarType::cases() as $type) {
            $defaults[$type->value] = QuoteCalculator::defaults($type);
        }

        $keys = array_keys($values['positions']);
        $next = $keys === [] ? 0 : max(array_map('intval', $keys)) + 1;
        $rows = $values['positions'] + [$next => []];

        return view('seminars.quote-form', [
            'quote' => $quote,
            'fromVersion' => $fromVersion,
            'values' => $values,
            'rows' => $rows,
            'nextIndex' => $next + 1,
            'preview' => $preview,
            'products' => SeminarType::options(),
            'defaults' => $defaults,
            'units' => collect(SeminarType::cases())->mapWithKeys(fn (SeminarType $type): array => [$type->value => $type->unit()])->all(),
            'partnerOptions' => Partner::query()->where('type', PartnerType::School->value)->orderBy('name')->get()
                ->mapWithKeys(fn (Partner $partner): array => [$partner->id => $partner->name.($partner->city ? ' ('.$partner->city.')' : '')])->all(),
            'seminarOptions' => Seminar::query()->with('partner')->whereNot('status', SeminarStatus::Cancelled->value)->orderByDesc('id')->limit(300)->get()
                ->mapWithKeys(fn (Seminar $seminar): array => [$seminar->id => $seminar->title.' – '.$seminar->partner->name])->all(),
        ]);
    }

    /**
     * Prüft die Positionen des Formulars (leere Zeilen ohne Produkt entfallen, die Zeilennummern bleiben erhalten).
     *
     * @param  array<string, mixed>  $extraRules
     * @param  array<string, string>  $extraMessages
     * @return array<string, mixed>
     */
    private function validatePositions(Request $request, array $extraRules, array $extraMessages): array
    {
        $request->merge(['positions' => collect((array) $request->input('positions', []))->filter(fn ($row): bool => is_array($row) && filled($row['product'] ?? null))->all()]);

        $rules = $extraRules + [
            'positions' => ['required', 'array', 'min:1', 'max:'.self::MAX_POSITIONS],
            'positions.*.product' => ['required', Rule::enum(SeminarType::class)],
            'positions.*.extra' => ['nullable', 'string', 'max:1000'],
        ];

        foreach (QuoteCalculator::rules() as $key => $rule) {
            $rules['positions.*.'.$key] = $rule;
        }

        return $request->validate($rules, $extraMessages + [
            'positions.required' => 'Bitte fügen Sie mindestens eine Position hinzu.',
            'positions.min' => 'Bitte fügen Sie mindestens eine Position hinzu.',
            'positions.max' => 'Ein Kostenvoranschlag kann höchstens '.self::MAX_POSITIONS.' Positionen haben.',
            'positions.*.product.required' => 'Bitte wählen Sie das Produkt.',
            'positions.*.product.enum' => 'Bitte wählen Sie ein bekanntes Produkt.',
            'positions.*.extra.max' => 'Die Ergänzung darf höchstens 1000 Zeichen lang sein.',
            'required' => 'Bitte füllen Sie dieses Feld aus.',
            'integer' => 'Bitte geben Sie eine ganze Zahl ein.',
            'numeric' => 'Bitte geben Sie eine Zahl ein.',
            'min' => 'Der Wert ist zu klein.',
            'max' => 'Der Wert ist zu groß.',
        ]);
    }

    /**
     * Stand des Kostenvoranschlags in einer Fassung (ohne Angabe: die aktuelle). Unbekannte Fassungen sind 404.
     *
     * @return array{version: int, positions: list<array<string, mixed>>, total_cents: int, price_list: \App\Modules\Seminars\Models\SeminarPriceList, at: \Illuminate\Support\Carbon, author: ?string, comment: ?string, current: bool}
     */
    private function versionData(SeminarQuote $quote, ?int $version): array
    {
        $version ??= $quote->version;
        $snapshot = $quote->versions()->with(['createdBy.person', 'priceList'])->where('version', $version)->firstOrFail();

        return [
            'version' => $snapshot->version,
            'positions' => $snapshot->positions,
            'total_cents' => $snapshot->total_cents,
            'price_list' => $snapshot->priceList,
            'at' => $snapshot->created_at,
            'author' => $snapshot->createdBy ? ($snapshot->createdBy->person?->fullName() ?: $snapshot->createdBy->email) : null,
            'comment' => $snapshot->comment,
            'current' => $snapshot->version === $quote->version,
        ];
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
