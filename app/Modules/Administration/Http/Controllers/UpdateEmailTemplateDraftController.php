<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UpdateEmailTemplateDraftController extends Controller
{
    public function __invoke(
        Request $request,
        EmailTemplate $emailTemplate,
        AdministrationAccess $access,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $access->canManage($actor), 403);

        $validated = $request->validate([
            'draft_subject' => ['required', 'string', 'max:255'],
            'draft_html' => ['required', 'string', 'max:100000'],
        ]);

        $emailTemplate->forceFill([
            'draft_subject' => $validated['draft_subject'],
            'draft_html' => $validated['draft_html'],
            'updated_by_user_id' => $actor->id,
        ])->save();

        return redirect()
            ->route('administration.communication.templates.show', $emailTemplate)
            ->with('status', 'Der Entwurf wurde gespeichert.')
            ->with('status_type', 'success');
    }
}
