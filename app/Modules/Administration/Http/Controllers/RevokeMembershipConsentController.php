<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\RevokeMembershipConsentAction;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RevokeMembershipConsentController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        MembershipConsent $membershipConsent,
        AdministrationAccess $access,
        RevokeMembershipConsentAction $revokeConsent,
    ): RedirectResponse {
        abort_unless($membershipConsent->membership_id === $membership->id, 404);

        $actor = $request->user();
        abort_unless($actor instanceof User && $access->canManage($actor), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $revokeConsent->execute(
            consent: $membershipConsent,
            actor: $actor,
            reason: (string) $validated['reason'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.memberships.show', $membership)
            ->with('status', 'Die Zustimmung wurde widerrufen; der Nachweis bleibt in der Historie erhalten.')
            ->with('status_type', 'success');
    }
}
