<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Identity\Actions\PortalInvitation\StartPortalInvitationWorkflowAction;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StartPersonPortalInvitationController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
        StartPortalInvitationWorkflowAction $startInvitation,
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
            $startInvitation->execute($person, $actor);

            return redirect()
                ->route('administration.persons.show', $person)
                ->with('status', 'Die Portal-Einladung wurde vorbereitet und zum Versand eingeplant.')
                ->with('status_type', 'success');
        } catch (EmailTemplateUnavailable) {
            return redirect()
                ->route('administration.persons.show', $person)
                ->with('status', 'Die Einladung wurde angelegt, konnte aber noch nicht versendet werden. Bitte das Einladungs-Template veröffentlichen und aktivieren und danach erneut senden.')
                ->with('status_type', 'warning');
        }
    }
}
