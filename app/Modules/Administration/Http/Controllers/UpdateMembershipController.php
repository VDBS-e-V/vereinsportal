<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\UpdateMembershipAction;
use App\Modules\Membership\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UpdateMembershipController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
        UpdateMembershipAction $updateMembership,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipsManage,
            ),
            403,
        );

        $updated = $updateMembership->execute(
            membership: $membership,
            values: $request->only(['starts_on', 'ends_on']),
            actor: $actor,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $updated)
            ->with('status', 'Die Mitgliedschaft wurde aktualisiert.')
            ->with('status_type', 'success');
    }
}
