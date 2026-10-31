<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarSession;
use App\Modules\Seminars\Services\SeminarAdministration;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Schulseminare (Bereich Koordination): Liste, Details, Anlegen, Bearbeiten, Status, Termine und Notizen. */
class SeminarController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly SeminarAdministration $seminars) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = SeminarStatus::tryFrom((string) $request->query('status', ''));
        $type = SeminarType::tryFrom((string) $request->query('type', ''));

        $query = Seminar::query()
            ->with(['partner', 'sessions'])
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn ($inner) => $inner->where('title', 'like', $like)->orWhereHas('partner', fn ($partner) => $partner->where('name', 'like', $like)->orWhere('city', 'like', $like)));
            })
            ->when($status !== null, fn ($builder) => $builder->where('status', $status?->value))
            ->when($type !== null, fn ($builder) => $builder->where('type', $type?->value))
            ->orderByDesc('updated_at')->orderByDesc('id');

        $filters = ['q' => $search !== '' ? $search : null, 'status' => $status?->value, 'type' => $type?->value];

        $active = collect([
            $filters['q'] !== null ? ['label' => 'Suche: '.$filters['q'], 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $status !== null ? ['label' => 'Status: '.$status->label(), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
            $type !== null ? ['label' => 'Leistung: '.$type->label(), 'href' => $request->fullUrlWithoutQuery(['type', 'page'])] : null,
        ])->filter()->values()->all();

        return view('seminars.index', [
            'seminars' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => $filters,
            'active' => $active,
            'statusOptions' => SeminarStatus::options(),
            'typeOptions' => SeminarType::options(),
            'stats' => [
                'total' => Seminar::query()->count(),
                'open' => Seminar::query()->whereIn('status', [SeminarStatus::Request->value, SeminarStatus::Planning->value])->count(),
                'planned' => Seminar::query()->whereIn('status', [SeminarStatus::Scheduled->value, SeminarStatus::Confirmed->value])->count(),
            ],
            'canManage' => Gate::allows('seminars.manage'),
        ]);
    }

    public function show(Seminar $seminar): View
    {
        $seminar->load(['partner', 'sessions', 'events.actor', 'quotes', 'createdBy.person', 'updatedBy.person', 'applications.user.person']);
        $team = app(\App\Modules\Seminars\Services\TeamApplications::class);

        return view('seminars.show', [
            'seminar' => $seminar,
            'nextStatuses' => $seminar->status->next(),
            'canManage' => Gate::allows('seminars.manage'),
            'teamOpen' => $team->isOpen($seminar),
            'teamConfirmed' => $seminar->applications->where('status', \App\Modules\Seminars\Enums\ApplicationStatus::Confirmed)->count(),
            'conflicts' => $seminar->applications->mapWithKeys(fn ($application): array => [$application->id => $team->conflicts($application->user, $seminar)->pluck('title')->all()]),
        ]);
    }

    public function create(Request $request): View
    {
        $partnerId = (int) $request->query('schule', 0);

        return view('seminars.form', $this->formData(null, ['partner_id' => $partnerId > 0 ? $partnerId : null, 'type' => SeminarType::SvDays->value]));
    }

    public function store(Request $request): RedirectResponse
    {
        $seminar = $this->seminars->create($this->validated($request), $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Das Seminar wurde angelegt.');
    }

    public function edit(Seminar $seminar): View
    {
        return view('seminars.form', $this->formData($seminar, array_merge($seminar->only(['title', 'partner_id', 'grade_level', 'expected_participants', 'notes']), ['type' => $seminar->type->value])));
    }

    public function update(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->seminars->update($seminar, $this->validated($request), $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Die Angaben wurden gespeichert.');
    }

    public function destroy(Request $request, Seminar $seminar): RedirectResponse
    {
        try {
            $this->seminars->delete($seminar, $this->actor($request));
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->with('warning', $exception->getMessage());
        }

        return redirect()->route('seminars.index')->with('success', 'Das Seminar wurde gelöscht.');
    }

    public function status(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(SeminarStatus::class)],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['status.required' => 'Bitte wählen Sie den neuen Status.', 'status.enum' => 'Bitte wählen Sie einen gültigen Status.']);

        try {
            $this->seminars->changeStatus($seminar, SeminarStatus::from($data['status']), $this->actor($request), $data['comment'] ?? null);
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->with('warning', $exception->getMessage());
        }

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Status wurde geändert.');
    }

    public function note(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Bitte schreiben Sie die Notiz.']);

        $this->seminars->note($seminar, $data['note'], $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Die Notiz wurde gespeichert.');
    }

    public function storeSession(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before_or_equal:'.now()->addYears(3)->toDateString()],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'location' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'date.required' => 'Bitte geben Sie das Datum an.',
            'date.date_format' => 'Bitte geben Sie das Datum an.',
            'date.after_or_equal' => 'Bitte prüfen Sie das Datum.',
            'date.before_or_equal' => 'Bitte prüfen Sie das Datum.',
            'start.required' => 'Bitte geben Sie den Beginn an.',
            'start.date_format' => 'Bitte geben Sie den Beginn an, z. B. 09:00.',
            'end.required' => 'Bitte geben Sie das Ende an.',
            'end.date_format' => 'Bitte geben Sie das Ende an, z. B. 15:30.',
            'end.after' => 'Das Ende muss nach dem Beginn liegen.',
        ]);

        $this->seminars->addSession($seminar, $data, $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Termin wurde eingetragen.');
    }

    public function destroySession(Request $request, Seminar $seminar, SeminarSession $session): RedirectResponse
    {
        abort_unless($session->seminar_id === $seminar->id, 404);

        $this->seminars->removeSession($seminar, $session, $this->actor($request));

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Termin wurde entfernt.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::enum(SeminarType::class)],
            'partner_id' => ['required', 'integer', Rule::exists('partners', 'id')->where('type', PartnerType::School->value)],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'expected_participants' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'Bitte geben Sie einen Titel ein.',
            'type.required' => 'Bitte wählen Sie die Leistung.',
            'type.enum' => 'Bitte wählen Sie eine bekannte Leistung.',
            'partner_id.required' => 'Bitte wählen Sie die Schule.',
            'partner_id.exists' => 'Bitte wählen Sie eine Schule aus der Liste.',
            'expected_participants.integer' => 'Die Teilnehmerzahl muss eine ganze Zahl sein.',
            'expected_participants.min' => 'Die Teilnehmerzahl muss mindestens 1 sein.',
            'expected_participants.max' => 'Bitte prüfen Sie die Teilnehmerzahl.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function formData(?Seminar $seminar, array $values): array
    {
        return [
            'seminar' => $seminar,
            'values' => $values,
            'typeOptions' => SeminarType::options(),
            'partnerOptions' => Partner::query()->where('type', PartnerType::School->value)->orderBy('name')->get()
                ->mapWithKeys(fn (Partner $partner): array => [$partner->id => $partner->name.($partner->city ? ' ('.$partner->city.')' : '')])->all(),
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
