<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Membership\Models\Membership;
use Illuminate\Contracts\View\View;

final class BoardHomeController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();

        return view('administration.board-home', [
            'totalMemberships' => Membership::query()->count(),
            'activeMemberships' => Membership::query()
                ->where('starts_on', '<=', $today)
                ->where(function ($query) use ($today): void {
                    $query
                        ->whereNull('ends_on')
                        ->orWhere('ends_on', '>=', $today);
                })
                ->count(),
            'plannedMemberships' => Membership::query()
                ->where('starts_on', '>', $today)
                ->count(),
            'endedMemberships' => Membership::query()
                ->where('ends_on', '<', $today)
                ->count(),
            'breadcrumbs' => [],
        ]);
    }
}
