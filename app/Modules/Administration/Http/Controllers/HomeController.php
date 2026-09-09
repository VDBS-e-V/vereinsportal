<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('administration.home', [
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()
                ->where('status', UserStatus::Active->value)
                ->count(),
            'pendingVerificationUsers' => User::query()
                ->where(
                    'status',
                    UserStatus::PendingVerification->value,
                )
                ->count(),
            'pendingDeletionUsers' => User::query()
                ->where(
                    'status',
                    UserStatus::PendingDeletion->value,
                )
                ->count(),
            'recentUsers' => User::query()
                ->with('person')
                ->latest('updated_at')
                ->limit(6)
                ->get(),
            'breadcrumbs' => [],
        ]);
    }
}
