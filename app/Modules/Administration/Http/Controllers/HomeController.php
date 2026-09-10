<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();

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
            'canManageAdministration' => $actor instanceof User
                && $access->canManage($actor),
            'breadcrumbs' => [],
        ]);
    }
}
