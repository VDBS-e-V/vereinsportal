<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Actions\PublishEmailTemplateAction;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PublishEmailTemplateController extends Controller
{
    public function __invoke(
        Request $request,
        EmailTemplate $emailTemplate,
        AdministrationAccess $access,
        PublishEmailTemplateAction $publishTemplate,
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

        $version = $publishTemplate->execute(
            templateId: $emailTemplate->id,
            publisher: $actor,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('administration.communication.templates.show', $emailTemplate)
            ->with('status', 'Version '.$version->version.' wurde veröffentlicht.')
            ->with('status_type', 'success');
    }
}
