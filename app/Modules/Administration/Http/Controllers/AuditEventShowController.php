<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Administration\Support\AuditEventPresentation;
use App\Modules\Administration\Support\AuditEventVisibility;
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
        AuditEventVisibility $visibility,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::AuditRead,
            ),
            403,
        );

        $canReadMemberships = $access->allowsCapability(
            $actor,
            AdministrationCapability::MembershipsRead,
        );

        abort_unless(
            $visibility->allows($auditEvent, $canReadMemberships),
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
