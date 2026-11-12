<?php

namespace App\Modules\AccessRequests\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\AccessRequests\Models\AccessRequestFile;
use App\Modules\AccessRequests\Services\AccessRequests;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Zugangsanträge prüfen (Bereich Geschäftsstelle): Liste, Details mit Nachweisen, in Prüfung nehmen, annehmen, ablehnen. */
class AccessRequestAdminController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly AccessRequests $requests) {}

    public function index(Request $request): View
    {
        $status = AccessRequestStatus::tryFrom((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));

        $query = AccessRequest::query()
            ->when($status !== null, fn ($builder) => $builder->where('status', $status?->value))
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn ($inner) => $inner->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('organization', 'like', $like));
            })
            ->orderByRaw("status IN ('approved', 'rejected')")->orderByDesc('created_at')->orderByDesc('id');

        $active = collect([
            $search !== '' ? ['label' => 'Suche: '.$search, 'href' => $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $status !== null ? ['label' => 'Status: '.$status->label(), 'href' => $request->fullUrlWithoutQuery(['status', 'page'])] : null,
        ])->filter()->values()->all();

        return view('access-requests.index', [
            'requests' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => ['q' => $search !== '' ? $search : null, 'status' => $status?->value],
            'active' => $active,
            'statusOptions' => AccessRequestStatus::options(),
            'stats' => [
                'new' => AccessRequest::query()->where('status', AccessRequestStatus::New->value)->count(),
                'review' => AccessRequest::query()->where('status', AccessRequestStatus::InReview->value)->count(),
                'decided' => AccessRequest::query()->whereNotNull('decided_at')->count(),
            ],
        ]);
    }

    public function show(AccessRequest $accessRequest): View
    {
        $accessRequest->load(['files', 'decidedBy.person', 'user']);

        return view('access-requests.show', [
            'accessRequest' => $accessRequest,
            'roleOptions' => [RoleKey::Team->value => RoleKey::Team->label()],
            'canManage' => Gate::allows('access_requests.manage'),
        ]);
    }

    public function review(AccessRequest $accessRequest): RedirectResponse
    {
        $this->requests->startReview($accessRequest);

        return redirect()->route('access-requests.show', $accessRequest)->with('success', 'Der Antrag ist jetzt in Prüfung.');
    }

    public function approve(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['nullable', Rule::in([RoleKey::Team->value])],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['role.in' => 'Bitte wählen Sie eine Rolle aus der Liste.']);

        try {
            $this->requests->approve($accessRequest, $this->actor($request), filled($data['role'] ?? null) ? RoleKey::from($data['role']) : null, $data['note'] ?? null);
        } catch (DomainException $exception) {
            return redirect()->route('access-requests.show', $accessRequest)->with('warning', $exception->getMessage());
        }

        return redirect()->route('access-requests.show', $accessRequest)->with('success', 'Der Antrag wurde angenommen. Die Einladung ist an '.$accessRequest->email.' gesendet.');
    }

    public function reject(Request $request, AccessRequest $accessRequest): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->requests->reject($accessRequest, $this->actor($request), $data['note'] ?? null);
        } catch (DomainException $exception) {
            return redirect()->route('access-requests.show', $accessRequest)->with('warning', $exception->getMessage());
        }

        return redirect()->route('access-requests.show', $accessRequest)->with('success', 'Der Antrag wurde abgelehnt.');
    }

    public function download(AccessRequest $accessRequest, AccessRequestFile $file): StreamedResponse
    {
        abort_unless($file->access_request_id === $accessRequest->id, 404);
        abort_unless(Storage::disk(AccessRequests::DISK)->exists($file->path), 404);

        return Storage::disk(AccessRequests::DISK)->download($file->path, $file->original_name, ['Content-Type' => $file->mime]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
