<?php

namespace App\Modules\Partners\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Enums\FederalState;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Enums\SchoolType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Services\PartnerAdministration;
use App\Modules\Partners\Services\PartnerImporter;
use App\Modules\Partners\Support\PartnerData;
use App\Support\CsvCell;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Schulen als Partner (Bereich Koordination): Liste, Details, Anlegen, Bearbeiten, Löschen, CSV-Export und -Import. */
class PartnerController extends Controller
{
    private const PER_PAGE = 20;

    private const SORTS = ['name', 'city', 'school_type', 'federal_state'];

    public function __construct(
        private readonly PartnerAdministration $partners,
        private readonly PartnerImporter $importer,
    ) {}

    public function index(Request $request): View
    {
        [$query, $filters, $sort, $direction] = $this->filtered($request);

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        $sortLink = fn (string $key): string => $request->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);

        $columns = [
            ['key' => 'name', 'label' => 'Schule', 'sortable' => true, 'href' => $sortLink('name')],
            ['key' => 'city', 'label' => 'Ort', 'sortable' => true, 'href' => $sortLink('city')],
            ['key' => 'school_type', 'label' => 'Schulart', 'sortable' => true, 'href' => $sortLink('school_type')],
            ['key' => 'federal_state', 'label' => 'Bundesland', 'sortable' => true, 'href' => $sortLink('federal_state')],
            ['key' => 'contact', 'label' => 'Ansprechperson'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $active = collect([
            $filters['q'] !== null ? ['label' => 'Suche: '.$filters['q'], 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $filters['status'] !== null ? ['label' => 'Status: '.PartnerStatus::from($filters['status'])->label(), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
            $filters['school_type'] !== null ? ['label' => 'Schulart: '.SchoolType::from($filters['school_type'])->label(), 'href' => $request->fullUrlWithoutQuery(['school_type', 'page'])] : null,
            $filters['federal_state'] !== null ? ['label' => 'Bundesland: '.FederalState::from($filters['federal_state'])->label(), 'href' => $request->fullUrlWithoutQuery(['federal_state', 'page'])] : null,
        ])->filter()->values()->all();

        $schools = Partner::query()->where('type', PartnerType::School->value);

        return view('partners.index', [
            'partners' => $paginator,
            'columns' => $columns,
            'sort' => $sort,
            'direction' => $direction,
            'active' => $active,
            'filters' => $filters,
            'statusOptions' => collect(PartnerStatus::cases())->mapWithKeys(fn (PartnerStatus $case): array => [$case->value => $case->label()])->all(),
            'typeOptions' => SchoolType::options(),
            'stateOptions' => FederalState::options(),
            'stats' => [
                'total' => (clone $schools)->count(),
                'active' => (clone $schools)->where('status', PartnerStatus::Active->value)->count(),
                'inactive' => (clone $schools)->where('status', PartnerStatus::Inactive->value)->count(),
            ],
            'exportUrl' => route('partners.export', array_filter($filters, fn ($value): bool => $value !== null) + array_filter(['sort' => $sort, 'direction' => $direction])),
            'canManage' => Gate::allows('partners.manage'),
        ]);
    }

    public function show(Partner $partner): View
    {
        abort_unless($partner->type === PartnerType::School, 404);

        $partner->load(['createdBy.person', 'updatedBy.person']);

        $attributes = $partner->getAttribute('attributes') ?? [];

        return view('partners.show', [
            'partner' => $partner,
            'schoolType' => isset($attributes['school_type']) ? SchoolType::tryFrom($attributes['school_type'])?->label() : null,
            'federalState' => isset($attributes['federal_state']) ? FederalState::tryFrom($attributes['federal_state'])?->label() : null,
            'schoolNumber' => $attributes['school_number'] ?? null,
            'studentCount' => $attributes['student_count'] ?? null,
            'canManage' => Gate::allows('partners.manage'),
            'history' => Gate::allows('audit.view') ? AuditEntry::query()->where('subject_type', 'partner')->where('subject_id', $partner->id)->orderByDesc('occurred_at')->orderByDesc('id')->limit(8)->get() : null,
        ]);
    }

    public function create(): View
    {
        return view('partners.form', $this->formData(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $partner = $this->partners->create($data, $this->actor($request));

        return redirect()->route('partners.show', $partner)->with('success', 'Die Schule wurde angelegt.');
    }

    public function edit(Partner $partner): View
    {
        abort_unless($partner->type === PartnerType::School, 404);

        return view('partners.form', $this->formData($partner));
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($partner->type === PartnerType::School, 404);

        $data = $this->validated($request, $partner);

        $this->partners->update($partner, $data, $this->actor($request));

        return redirect()->route('partners.show', $partner)->with('success', 'Die Angaben wurden gespeichert.');
    }

    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($partner->type === PartnerType::School, 404);

        $this->partners->delete($partner, $this->actor($request));

        return redirect()->route('partners.index')->with('success', 'Die Schule wurde gelöscht.');
    }

    public function export(Request $request): StreamedResponse
    {
        [$query] = $this->filtered($request);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8-Kennzeichen, damit Excel Umlaute richtig zeigt
            fputcsv($out, PartnerImporter::template()[0], ';', '"', '\\');

            foreach ($query->cursor() as $partner) {
                /** @var Partner $partner */
                $attributes = $partner->getAttribute('attributes') ?? [];

                fputcsv($out, array_map([CsvCell::class, 'safe'], [
                    $partner->name,
                    $partner->legal_name,
                    isset($attributes['school_type']) ? (SchoolType::tryFrom($attributes['school_type'])?->label() ?? $attributes['school_type']) : '',
                    isset($attributes['federal_state']) ? (FederalState::tryFrom($attributes['federal_state'])?->label() ?? $attributes['federal_state']) : '',
                    $attributes['school_number'] ?? '',
                    $partner->street,
                    $partner->house_number,
                    $partner->postal_code,
                    $partner->city,
                    $partner->contact_name,
                    $partner->contact_email,
                    $partner->contact_phone,
                    $attributes['student_count'] ?? '',
                    $partner->status === PartnerStatus::Active ? 'aktiv' : 'inaktiv',
                    $partner->notes,
                ]), ';', '"', '\\');
            }

            fclose($out);
        }, 'schulen-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importForm(): View
    {
        return view('partners.import');
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach (PartnerImporter::template() as $row) {
                fputcsv($out, $row, ';', '"', '\\');
            }

            fclose($out);
        }, 'schulen-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Bitte wählen Sie eine CSV-Datei.',
            'file.mimes' => 'Erlaubt sind CSV-Dateien.',
            'file.max' => 'Die Datei darf höchstens 2 MB groß sein.',
        ]);

        $result = $this->importer->import((string) file_get_contents((string) $request->file('file')?->getRealPath()), $this->actor($request));

        if ($result['errors'] !== []) {
            return redirect()->route('partners.import')->with('import_errors', array_slice($result['errors'], 0, 50))->with('import_error_count', count($result['errors']));
        }

        return redirect()->route('partners.index')->with('success', "{$result['created']} Schulen importiert".($result['skipped'] > 0 ? ", {$result['skipped']} übersprungen (gibt es schon)." : '.'));
    }

    /**
     * @return array{0: Builder<Partner>, 1: array<string, ?string>, 2: string, 3: string}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $status = PartnerStatus::tryFrom((string) $request->query('status', ''));
        $type = SchoolType::tryFrom((string) $request->query('school_type', ''));
        $state = FederalState::tryFrom((string) $request->query('federal_state', ''));
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Partner::query()
            ->where('type', PartnerType::School->value)
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $builder->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', $like)->orWhere('legal_name', 'like', $like)->orWhere('city', 'like', $like)
                    ->orWhere('postal_code', 'like', $like)->orWhere('contact_name', 'like', $like));
            })
            ->when($status !== null, fn (Builder $builder) => $builder->where('status', $status?->value))
            ->when($type !== null, fn (Builder $builder) => $builder->where('attributes->school_type', $type?->value))
            ->when($state !== null, fn (Builder $builder) => $builder->where('attributes->federal_state', $state?->value));

        match ($sort) {
            'city' => $query->orderBy('city', $direction)->orderBy('name'),
            'school_type' => $query->orderBy('attributes->school_type', $direction)->orderBy('name'),
            'federal_state' => $query->orderBy('attributes->federal_state', $direction)->orderBy('name'),
            default => $query->orderBy('name', $direction)->orderBy('id'),
        };

        return [$query, ['q' => $search !== '' ? $search : null, 'status' => $status?->value, 'school_type' => $type?->value, 'federal_state' => $state?->value], $sort, $direction];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Partner $partner): array
    {
        $data = $request->validate(PartnerData::rules(), PartnerData::messages());

        if ($this->partners->exists($data['name'], $data['city'] ?? null, $partner?->id)) {
            throw ValidationException::withMessages(['name' => 'Diese Schule ist an diesem Ort schon erfasst.']);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function formData(?Partner $partner): array
    {
        return [
            'partner' => $partner,
            'values' => $partner !== null ? PartnerData::fromPartner($partner) : ['status' => PartnerStatus::Active->value, 'country_code' => 'DE'],
            'typeOptions' => SchoolType::options(),
            'stateOptions' => FederalState::options(),
            'statusOptions' => collect(PartnerStatus::cases())->mapWithKeys(fn (PartnerStatus $case): array => [$case->value => $case->label()])->all(),
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
