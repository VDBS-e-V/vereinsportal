<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Administration\Support\AuditEventVisibility;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class AuditEventIndexController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
        AuditEventVisibility $visibility,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::AuditRead,
            ),
            403,
        );

        $canReadMemberships = $access->allowsCapability(
            $actor,
            AdministrationCapability::MembershipsRead,
        );

        $eventKey = mb_substr(
            trim((string) $request->query('event_key', '')),
            0,
            150,
        );
        $subjectType = mb_substr(
            trim((string) $request->query('subject_type', '')),
            0,
            150,
        );
        $actorIdInput = trim((string) $request->query('actor_id', ''));
        $subjectIdInput = trim((string) $request->query('subject_id', ''));
        $from = $this->validDate($request->query('from'));
        $to = $this->validDate($request->query('to'));
        $actorId = ctype_digit($actorIdInput)
            ? (int) $actorIdInput
            : null;
        $subjectId = ctype_digit($subjectIdInput)
            ? (int) $subjectIdInput
            : null;

        $events = $visibility
            ->constrain(
                AuditEvent::query()->with('actor.person'),
                $canReadMemberships,
            )
            ->when(
                $eventKey !== '',
                fn (Builder $query) => $query->where(
                    'event_key',
                    $eventKey,
                ),
            )
            ->when(
                $actorId !== null,
                fn (Builder $query) => $query->where(
                    'actor_user_id',
                    $actorId,
                ),
            )
            ->when(
                $subjectType !== '',
                fn (Builder $query) => $query->where(
                    'subject_type',
                    $subjectType,
                ),
            )
            ->when(
                $subjectId !== null,
                fn (Builder $query) => $query->where(
                    'subject_id',
                    $subjectId,
                ),
            )
            ->when(
                $from !== null,
                fn (Builder $query) => $query->where(
                    'occurred_at',
                    '>=',
                    $from.' 00:00:00',
                ),
            )
            ->when(
                $to !== null,
                fn (Builder $query) => $query->where(
                    'occurred_at',
                    '<=',
                    $to.' 23:59:59',
                ),
            )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $eventKeys = $visibility
            ->constrain(AuditEvent::query(), $canReadMemberships)
            ->select('event_key')
            ->distinct()
            ->orderBy('event_key')
            ->pluck('event_key');

        $subjectTypes = $visibility
            ->constrain(AuditEvent::query(), $canReadMemberships)
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $actorIds = $visibility
            ->constrain(AuditEvent::query(), $canReadMemberships)
            ->whereNotNull('actor_user_id')
            ->select('actor_user_id')
            ->distinct()
            ->pluck('actor_user_id');

        $actors = User::query()
            ->with('person')
            ->whereKey($actorIds->all())
            ->orderBy('email')
            ->get();

        return view('administration.audit.index', [
            'events' => $events,
            'eventKeys' => $eventKeys,
            'subjectTypes' => $subjectTypes,
            'actors' => $actors,
            'filters' => [
                'event_key' => $eventKey,
                'actor_id' => $actorIdInput,
                'subject_type' => $subjectType,
                'subject_id' => $subjectIdInput,
                'from' => $from ?? '',
                'to' => $to ?? '',
            ],
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Audit',
                    'url' => null,
                ],
            ],
        ]);
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map(
            'intval',
            explode('-', $value),
        );

        return checkdate($month, $day, $year)
            ? $value
            : null;
    }
}
