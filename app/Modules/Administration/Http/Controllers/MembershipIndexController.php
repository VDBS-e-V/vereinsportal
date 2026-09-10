<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MembershipIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $searchValue = $request->query('q');
        $statusValue = $request->query('status');

        $search = is_string($searchValue)
            ? trim($searchValue)
            : '';

        $status = is_string($statusValue)
            ? MembershipStatus::tryFrom($statusValue)
            : null;

        $today = now()->toDateString();

        $memberships = Membership::query()
            ->with(['person.user'])
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->whereHas(
                        'person',
                        function (Builder $query) use ($search): void {
                            $query->where(function (Builder $query) use ($search): void {
                                $query
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                        },
                    );
                },
            )
            ->when(
                $status === MembershipStatus::Planned,
                fn (Builder $query): Builder => $query->where('starts_on', '>', $today),
            )
            ->when(
                $status === MembershipStatus::Active,
                fn (Builder $query): Builder => $query
                    ->where('starts_on', '<=', $today)
                    ->where(function (Builder $query) use ($today): void {
                        $query
                            ->whereNull('ends_on')
                            ->orWhere('ends_on', '>=', $today);
                    }),
            )
            ->when(
                $status === MembershipStatus::Ended,
                fn (Builder $query): Builder => $query->where('ends_on', '<', $today),
            )
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('administration.memberships.index', [
            'memberships' => $memberships,
            'search' => $search,
            'status' => $status,
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Mitgliedschaften',
                    'url' => null,
                ],
            ],
        ]);
    }
}
