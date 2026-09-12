<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Actions\ActivateEmailTemplateAction;
use App\Modules\Communication\Actions\DeactivateEmailTemplateAction;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UpdateEmailTemplateStatusController extends Controller
{
    public function __invoke(
        Request $request,
        EmailTemplate $emailTemplate,
        AdministrationAccess $access,
        ActivateEmailTemplateAction $activateTemplate,
        DeactivateEmailTemplateAction $deactivateTemplate,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::CommunicationManage,
            ),
            403,
        );

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        if ($validated['active']) {
            $activateTemplate->execute(
                templateId: $emailTemplate->id,
                actor: $actor,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
            $message = 'Die Vorlage wurde aktiviert.';
        } else {
            $deactivateTemplate->execute(
                templateId: $emailTemplate->id,
                actor: $actor,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
            $message = 'Die Vorlage wurde deaktiviert.';
        }

        return redirect()
            ->route('administration.communication.templates.show', $emailTemplate)
            ->with('status', $message)
            ->with('status_type', 'success');
    }
}
