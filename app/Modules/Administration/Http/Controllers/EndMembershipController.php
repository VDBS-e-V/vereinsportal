<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\EndMembershipAction;
use App\Modules\Membership\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EndMembershipController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
        EndMembershipAction $endMembership,
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

        $endsOn = $request->input('ends_on');
        $reason = $request->input('reason');

        $updated = $endMembership->execute(
            membership: $membership,
            endsOn: is_string($endsOn) ? $endsOn : '',
            reason: is_string($reason) ? $reason : '',
            actor: $actor,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $updated)
            ->with('status', 'Das Enddatum der Mitgliedschaft wurde gesetzt.')
            ->with('status_type', 'success');
    }
}
