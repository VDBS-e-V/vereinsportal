<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Identity\Actions\PortalInvitation\ResendPortalInvitationWorkflowAction;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ResendPortalInvitationController extends Controller
{
    public function __invoke(
        Request $request,
        PortalInvitation $portalInvitation,
        AdministrationAccess $access,
        ResendPortalInvitationWorkflowAction $resendInvitation,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::PortalInvitationsManage,
            ),
            403,
        );

        try {
            $resendInvitation->execute($portalInvitation, $actor);

            return redirect()
                ->route('administration.persons.show', $portalInvitation->person_id)
                ->with('status', 'Die Portal-Einladung wurde mit einem neuen Link erneut versendet.')
                ->with('status_type', 'success');
        } catch (EmailTemplateUnavailable) {
            return redirect()
                ->route('administration.persons.show', $portalInvitation->person_id)
                ->with('status', 'Der Einladungslink wurde erneuert, konnte aber noch nicht versendet werden. Bitte das Einladungs-Template veröffentlichen und aktivieren und danach erneut senden.')
                ->with('status_type', 'warning');
        }
    }
}
