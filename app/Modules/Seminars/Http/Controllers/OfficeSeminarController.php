<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Services\SeminarWorkflow;
use App\Modules\Seminars\Support\SchoolContractUpload;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Geschäftsstelle: Liste aller Seminare mit Filtern, Seminarseite mit Verlauf und den einfachen Statuswechseln. */
class OfficeSeminarController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(private readonly SeminarWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')) ?: null,
            'status' => SeminarStatus::tryFrom((string) $request->query('status', ''))?->value,
            'coordination' => $request->integer('coordination') ?: null,
            'school_year' => preg_match('~^\d{4}/\d{2}$~', (string) $request->query('school_year', '')) === 1 ? (string) $request->query('school_year') : null,
        ];

        $query = Seminar::query()->with(['partner', 'coordination'])
            ->when($filters['q'], fn (Builder $query, string $q) => $query->where(fn (Builder $inner) => $inner->where('title', 'like', '%'.$q.'%')->orWhere('number', 'like', '%'.$q.'%')->orWhereHas('partner', fn (Builder $partner) => $partner->where('name', 'like', '%'.$q.'%'))))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['coordination'], fn (Builder $query, int $id) => $query->where('coordination_id', $id))
            ->when($filters['school_year'], fn (Builder $query, string $year) => $query->where('school_year', $year))
            ->orderByDesc('id');

        $active = collect([
            $filters['q'] !== null ? ['label' => 'Suche: '.$filters['q'], 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $filters['status'] !== null ? ['label' => 'Status: '.SeminarStatus::from($filters['status'])->label(), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
            $filters['coordination'] !== null ? ['label' => 'Koordination: '.(Coordination::query()->whereKey($filters['coordination'])->value('name') ?? '–'), 'href' => $request->fullUrlWithoutQuery(['coordination', 'page'])] : null,
            $filters['school_year'] !== null ? ['label' => 'Schuljahr: '.$filters['school_year'], 'href' => $request->fullUrlWithoutQuery(['school_year', 'page'])] : null,
        ])->filter()->values()->all();

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        return view('seminars.office-index', [
            'seminars' => $paginator,
            'filters' => $filters,
            'active' => $active,
            'statusOptions' => SeminarStatus::options(),
            'coordinationOptions' => Coordination::query()->ordered()->pluck('name', 'id')->all(),
            'schoolYears' => Seminar::query()->whereNotNull('school_year')->distinct()->orderByDesc('school_year')->pluck('school_year', 'school_year')->all(),
        ]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        $user = $this->user($request);

        return view('seminars.office-show', [
            'seminar' => $seminar->load(['partner.coordination', 'coordination', 'sessions', 'events.actor', 'requestedBy.person']),
            'targets' => $this->workflow->simpleTargets($seminar, $user),
        ]);
    }

    public function status(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', Rule::enum(SeminarStatus::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $to = SeminarStatus::from($data['to']);

        // Nur die einfachen Schritte gehen über diese Adresse; Freigabe, KVA, Team und Bericht haben eigene Aktionen.
        if (! in_array($to, $this->workflow->simpleTargets($seminar, $this->user($request)), true)) {
            return back()->withErrors(['to' => 'Dieser Schritt ist für dieses Seminar zurzeit nicht möglich.']);
        }

        try {
            $this->workflow->transition($seminar, $to, $this->user($request), $data['comment'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['comment' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.office.show', $seminar)->with('success', 'Der Status ist jetzt „'.$to->label().'“.');
    }

    /** Freigabe mit Schulvertrag oder auf Zusage der Schule. */
    public function release(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['basis' => ['required', Rule::in(['contract', 'commitment'])], 'reason' => ['nullable', 'string', 'max:2000']], ['basis.required' => 'Bitte wählen Sie die Grundlage der Freigabe.']);

        try {
            $this->workflow->release($seminar, $this->user($request), $data['basis'], $data['reason'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['release' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('seminars.office.show', $seminar)->with('success', 'Das Seminar ist freigegeben. Die Koordination kann es jetzt planen.');
    }

    /** Unterschriebenen Schulvertrag (von der Schule gestellt) hochladen. */
    public function contract(Request $request, Seminar $seminar, SeminarDocuments $documents): RedirectResponse
    {
        return SchoolContractUpload::handle($request, $seminar, $documents, $this->user($request), redirect()->route('seminars.office.show', $seminar));
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
