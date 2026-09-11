<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class EmailTemplateShowController extends Controller
{
    public function __invoke(
        Request $request,
        EmailTemplate $emailTemplate,
        AdministrationAccess $access,
    ): View {
        $emailTemplate->load([
            'placeholders' => fn ($query) => $query->orderBy('key'),
            'versions' => fn ($query) => $query->with('publishedBy')->orderByDesc('version'),
            'updatedBy',
        ]);

        $actor = $request->user();

        return view('administration.communication.templates.show', [
            'template' => $emailTemplate,
            'canManage' => $actor instanceof User && $access->canManage($actor),
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Kommunikation', 'url' => route('administration.communication.templates.index')],
                ['label' => $emailTemplate->name, 'url' => null],
            ],
        ]);
    }
}
