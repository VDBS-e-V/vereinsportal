<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\CreateMembershipAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StoreMembershipController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
        CreateMembershipAction $createMembership,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->canManage($actor),
            403,
        );

        $membership = $createMembership->execute(
            person: $person,
            values: $request->only(['starts_on', 'ends_on']),
            actor: $actor,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $membership)
            ->with('status', 'Die Mitgliedschaft wurde angelegt.')
            ->with('status_type', 'success');
    }
}
