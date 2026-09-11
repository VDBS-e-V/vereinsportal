<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Models\EmailTemplate;
use Illuminate\Contracts\View\View;

final class EmailTemplateIndexController extends Controller
{
    public function __invoke(): View
    {
        $templates = EmailTemplate::query()
            ->withCount('versions')
            ->orderBy('name')
            ->paginate(25);

        return view('administration.communication.templates.index', [
            'templates' => $templates,
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Kommunikation', 'url' => null],
            ],
        ]);
    }
}
