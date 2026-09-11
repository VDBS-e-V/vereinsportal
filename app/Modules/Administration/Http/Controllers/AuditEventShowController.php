<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Administration\Support\AuditEventPresentation;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class AuditEventShowController extends Controller
{
    public function __invoke(
        Request $request,
        AuditEvent $auditEvent,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->canManage($actor),
            403,
        );

        $auditEvent->load('actor.person');

        return view('administration.audit.show', [
            'auditEvent' => $auditEvent,
            'subjectUrl' => AuditEventPresentation::subjectUrl(
                $auditEvent,
            ),
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Audit',
                    'url' => route('administration.audit.index'),
                ],
                [
                    'label' => '#'.$auditEvent->id,
                    'url' => null,
                ],
            ],
        ]);
    }
}
