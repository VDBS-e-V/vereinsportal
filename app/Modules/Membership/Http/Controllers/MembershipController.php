<?php

namespace App\Modules\Membership\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use App\Modules\Membership\Services\MembershipDocuments;
use App\Modules\Membership\Services\MembershipRegistrations;
use App\Modules\Membership\Services\Memberships;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Mitgliedschaften (Bereich Vorstand): Liste, Details mit Verlauf, Antrag, Aufnahme, Ablehnung, Austritt. */
class MembershipController extends Controller
{
    private const PER_PAGE = 15;

    private const SORTS = ['name', 'requested_on', 'starts_on', 'ends_on'];

    private const FILTERS = [
        'requested' => 'Beantragt',
        'active' => 'Aktiv',
        'ending' => 'Austritt eingetragen',
        'ended' => 'Beendet',
        'rejected' => 'Abgelehnt',
    ];

    public function __construct(
        private readonly Memberships $memberships,
        private readonly MembershipDocuments $documents,
        private readonly MembershipRegistrations $registrations,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = array_key_exists((string) $request->query('status', ''), self::FILTERS) ? (string) $request->query('status') : null;
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Membership::query()
            ->leftJoin('persons', 'persons.id', '=', 'memberships.person_id')
            ->select('memberships.*')
            ->with('person')
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $builder->where(fn (Builder $inner) => $inner
                    ->where('persons.first_name', 'like', $like)->orWhere('persons.last_name', 'like', $like)
                    ->orWhere('memberships.first_name', 'like', $like)->orWhere('memberships.last_name', 'like', $like));
            });

        $this->applyStatusFilter($query, $status);

        match ($sort) {
            'requested_on' => $query->orderBy('memberships.requested_on', $direction)->orderByRaw('COALESCE(persons.last_name, memberships.last_name)'),
            'starts_on' => $query->orderBy('memberships.starts_on', $direction)->orderByRaw('COALESCE(persons.last_name, memberships.last_name)'),
            'ends_on' => $query->orderByRaw('memberships.ends_on IS NULL')->orderBy('memberships.ends_on', $direction),
            default => $query->orderByRaw('COALESCE(persons.last_name, memberships.last_name) '.$direction)->orderByRaw('COALESCE(persons.first_name, memberships.first_name) '.$direction)->orderBy('memberships.id'),
        };

        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        $sortLink = fn (string $key): string => $request->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
            'page' => null,
        ]);

        $columns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'href' => $sortLink('name')],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'requested_on', 'label' => 'Antrag', 'sortable' => true, 'href' => $sortLink('requested_on')],
            ['key' => 'starts_on', 'label' => 'Mitglied seit', 'sortable' => true, 'href' => $sortLink('starts_on')],
            ['key' => 'ends_on', 'label' => 'Austritt zum', 'sortable' => true, 'href' => $sortLink('ends_on')],
        ];

        $active = collect([
            $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $status !== null ? ['label' => 'Status: '.self::FILTERS[$status], 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
        ])->filter()->values()->all();

        $count = fn (string $filter): int => $this->applyStatusFilter(Membership::query(), $filter)->count();

        return view('membership.index', [
            'memberships' => $paginator,
            'columns' => $columns,
            'sort' => $sort,
            'direction' => $direction,
            'active' => $active,
            'filters' => ['q' => $search, 'status' => $status],
            'statusOptions' => self::FILTERS,
            'stats' => ['requested' => $count('requested'), 'active' => $count('active'), 'ending' => $count('ending'), 'ended' => $count('ended')],
            'canManage' => Gate::allows('memberships.manage'),
        ]);
    }

    public function show(Membership $membership): View
    {
        $membership->load(['person.user', 'decidedBy.person', 'events.actor', 'documents', 'consents']);

        return view('membership.show', [
            'membership' => $membership,
            'person' => $membership->person,
            'status' => $membership->effectiveStatus(),
            'events' => $membership->events,
            'documents' => $membership->documents,
            'consents' => $membership->consents,
            'documentKinds' => MembershipDocument::KINDS,
            'canManage' => Gate::allows('memberships.manage'),
            'canViewPerson' => Gate::allows('people.view'),
        ]);
    }

    public function create(): View
    {
        return view('membership.create', ['documentKinds' => MembershipDocument::KINDS]);
    }

    /** Erfasst einen Antrag oder Altbestand manuell (ohne digitale Person) mit Dokument. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['nullable', 'string', 'email', 'max:254'],
            'mode' => ['required', 'in:request,admit'],
            'starts_on' => ['required_if:mode,admit', 'nullable', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:'.now()->addYear()->toDateString()],
            'kind' => ['required', 'in:'.implode(',', array_keys(MembershipDocument::KINDS))],
            'document' => $this->documentRules(true),
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'first_name.required' => 'Bitte geben Sie den Vornamen ein.',
            'last_name.required' => 'Bitte geben Sie den Nachnamen ein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'mode.required' => 'Bitte wählen Sie, wie die Mitgliedschaft angelegt wird.',
            'starts_on.required_if' => 'Bitte geben Sie das Beitrittsdatum an.',
            'starts_on.before_or_equal' => 'Das Beitrittsdatum darf höchstens ein Jahr in der Zukunft liegen.',
            'document.required' => 'Bitte laden Sie das Dokument hoch.',
            'document.mimes' => 'Erlaubt sind PDF-, JPEG- und PNG-Dateien.',
            'document.max' => 'Die Datei darf höchstens 10 MB groß sein.',
        ]);

        try {
            $membership = $this->memberships->createManual(
                ['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'birth_date' => $data['birth_date'] ?? null, 'email' => $data['email'] ?? null],
                $request->file('document'),
                $data['kind'],
                $this->actor($request),
                $data['mode'] === 'admit' ? Carbon::parse($data['starts_on']) : null,
                $data['comment'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['last_name' => $exception->getMessage()]);
        }

        return redirect()->route('membership.show', $membership)->with('success', $data['mode'] === 'admit' ? 'Die Mitgliedschaft wurde erfasst.' : 'Der Antrag wurde erfasst.');
    }

    public function storeDocument(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', array_keys(MembershipDocument::KINDS))],
            'document' => $this->documentRules(true),
        ], [
            'document.required' => 'Bitte wählen Sie eine Datei.',
            'document.mimes' => 'Erlaubt sind PDF-, JPEG- und PNG-Dateien.',
            'document.max' => 'Die Datei darf höchstens 10 MB groß sein.',
        ]);

        return $this->perform(fn () => $this->documents->store($membership, $request->file('document'), $data['kind'], $this->actor($request)), $membership, 'Das Dokument wurde gespeichert.');
    }

    /** Liefert ein Dokument aus dem privaten Speicher als Download. */
    public function downloadDocument(Membership $membership, MembershipDocument $document): StreamedResponse
    {
        abort_unless($document->membership_id === $membership->id && Storage::disk(MembershipDocuments::DISK)->exists($document->path), 404);

        return Storage::disk(MembershipDocuments::DISK)->download($document->path, $document->original_name, [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroyDocument(Request $request, Membership $membership, MembershipDocument $document): RedirectResponse
    {
        abort_unless($document->membership_id === $membership->id, 404);

        return $this->perform(fn () => $this->documents->delete($document, $this->actor($request)), $membership, 'Das Dokument wurde entfernt.');
    }

    public function sendRegistrationLink(Request $request, Membership $membership): RedirectResponse
    {
        return $this->perform(fn () => $this->registrations->sendLink($membership, $this->actor($request)), $membership, 'Der Registrierungslink wurde an '.$membership->email.' gesendet.');
    }

    public function admit(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate([
            'starts_on' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:'.now()->addYear()->toDateString()],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['starts_on.required' => 'Bitte geben Sie das Beitrittsdatum an.']);

        return $this->perform(fn () => $this->memberships->admit($membership, Carbon::parse($data['starts_on']), $this->actor($request), $data['comment'] ?? null), $membership, 'Die Person wurde aufgenommen.');
    }

    public function reject(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate(['comment' => ['required', 'string', 'max:500']], ['comment.required' => 'Bitte nennen Sie den Grund für die Ablehnung.']);

        return $this->perform(fn () => $this->memberships->reject($membership, $data['comment'], $this->actor($request)), $membership, 'Der Antrag wurde abgelehnt.');
    }

    public function terminate(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate([
            'ends_on' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:'.now()->addYears(10)->toDateString()],
            'comment' => ['nullable', 'string', 'max:500'],
        ], ['ends_on.required' => 'Bitte geben Sie das Austrittsdatum an.']);

        return $this->perform(fn () => $this->memberships->terminate($membership, Carbon::parse($data['ends_on']), $this->actor($request), $data['comment'] ?? null), $membership, 'Der Austritt wurde eingetragen.');
    }

    public function withdraw(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:500']]);

        return $this->perform(fn () => $this->memberships->withdrawTermination($membership, $this->actor($request), $data['comment'] ?? null), $membership, 'Der Austritt wurde zurückgenommen.');
    }

    public function note(Request $request, Membership $membership): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Bitte geben Sie die Notiz ein.']);

        return $this->perform(fn () => $this->memberships->note($membership, $data['note'], $this->actor($request)), $membership, 'Die Notiz wurde gespeichert.');
    }

    /** @return list<string> */
    private function documentRules(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.MembershipDocuments::MAX_KILOBYTES];
    }

    /** Filtert nach dem wirksamen Status (aktiv gilt nur bis zum Austrittsdatum). */
    private function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        $today = today();

        return match ($status) {
            'requested' => $query->where('memberships.status', MembershipStatus::Requested->value),
            'active' => $query->where('memberships.status', MembershipStatus::Active->value)->where(fn (Builder $end) => $end->whereNull('memberships.ends_on')->orWhere('memberships.ends_on', '>=', $today)),
            'ending' => $query->where('memberships.status', MembershipStatus::Active->value)->where('memberships.ends_on', '>=', $today),
            'ended' => $query->where(fn (Builder $ended) => $ended
                ->where('memberships.status', MembershipStatus::Ended->value)
                ->orWhere(fn (Builder $past) => $past->where('memberships.status', MembershipStatus::Active->value)->where('memberships.ends_on', '<', $today))),
            'rejected' => $query->where('memberships.status', MembershipStatus::Rejected->value),
            default => $query,
        };
    }

    private function perform(callable $action, Membership $membership, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return redirect()->route('membership.show', $membership)->with('warning', $exception->getMessage());
        }

        return redirect()->route('membership.show', $membership)->with('success', $success);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
