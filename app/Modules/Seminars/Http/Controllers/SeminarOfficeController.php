<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\Partner;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarWorkflow;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Geschäftsstelle: alle Seminare in einer Liste, mit KVA, Freigabe und Verträgen (Berechtigung seminars.review). Die interne Freigabe des KVA gehört zu seminars.quote_approve. */
class SeminarOfficeController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(private readonly SeminarWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = SeminarStatus::tryFrom((string) $request->query('status', ''));
        $area = trim((string) $request->query('area', ''));

        $seminars = Seminar::query()
            ->with(['partner', 'sessions'])
            ->withCount(['quotes'])
            ->when($search !== '', function ($builder) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $builder->where(fn ($inner) => $inner->where('number', 'like', $like)->orWhere('title', 'like', $like)->orWhereHas('partner', fn ($partner) => $partner->where('name', 'like', $like)->orWhere('city', 'like', $like)));
            })
            ->when($status !== null, fn ($builder) => $builder->where('status', $status?->value))
            ->when($area !== '', fn ($builder) => $builder->whereHas('partner', fn ($partner) => $partner->where('coordination_area', $area)))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('seminars.office', [
            'seminars' => $seminars,
            'filters' => ['q' => $search, 'status' => $status?->value, 'area' => $area],
            'statusOptions' => SeminarStatus::options(),
            'areas' => Partner::query()->whereNotNull('coordination_area')->distinct()->orderBy('coordination_area')->pluck('coordination_area', 'coordination_area')->all(),
            'workflow' => $this->workflow,
        ]);
    }

    public function approveQuote(Request $request, Seminar $seminar): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        try {
            $this->workflow->approveQuote($seminar, $user);
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->with('warning', $exception->getMessage());
        }

        return redirect()->route('seminars.show', $seminar)->with('success', 'Der Kostenvoranschlag ist intern freigegeben. Die Geschäftsstelle kann ihn jetzt an die Schule senden.');
    }
}
