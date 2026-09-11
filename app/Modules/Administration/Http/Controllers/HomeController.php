<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $canReadUsers = $access->allowsCapability(
            $actor,
            AdministrationCapability::UsersRead,
        );

        return view('administration.home', [
            'totalUsers' => $canReadUsers
                ? User::query()->count()
                : null,
            'activeUsers' => $canReadUsers
                ? User::query()
                    ->where('status', UserStatus::Active->value)
                    ->count()
                : null,
            'pendingVerificationUsers' => $canReadUsers
                ? User::query()
                    ->where(
                        'status',
                        UserStatus::PendingVerification->value,
                    )
                    ->count()
                : null,
            'pendingDeletionUsers' => $canReadUsers
                ? User::query()
                    ->where(
                        'status',
                        UserStatus::PendingDeletion->value,
                    )
                    ->count()
                : null,
            'recentUsers' => $canReadUsers
                ? User::query()
                    ->with('person')
                    ->latest('updated_at')
                    ->limit(6)
                    ->get()
                : new Collection(),
            'canReadUsers' => $canReadUsers,
            'canReadPersons' => $access->allowsCapability(
                $actor,
                AdministrationCapability::PersonsRead,
            ),
            'canReadMemberships' => $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipsRead,
            ),
            'canReadCommunication' => $access->allowsCapability(
                $actor,
                AdministrationCapability::CommunicationRead,
            ),
            'canReadAudit' => $access->allowsCapability(
                $actor,
                AdministrationCapability::AuditRead,
            ),
            'breadcrumbs' => [],
        ]);
    }
}
