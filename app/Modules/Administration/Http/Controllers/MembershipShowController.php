<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Enums\MembershipConsentSource;
use App\Modules\Membership\Enums\MembershipDocumentType;
use App\Modules\Membership\Models\Membership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class MembershipShowController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $canReadDocuments = $access->allowsCapability(
            $actor,
            AdministrationCapability::MembershipDocumentsRead,
        );
        $canReadConsents = $access->allowsCapability(
            $actor,
            AdministrationCapability::MembershipConsentsRead,
        );
        $canReadUsers = $access->allowsCapability(
            $actor,
            AdministrationCapability::UsersRead,
        );

        $relations = ['person'];

        if ($canReadUsers) {
            $relations[] = 'person.user';
        }

        if ($canReadDocuments) {
            $relations['documents'] = fn ($query) => $query
                ->with(['uploadedBy', 'supersededBy'])
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        }

        if ($canReadConsents) {
            $relations['consents'] = fn ($query) => $query
                ->with(['recordedBy', 'revokedBy'])
                ->orderByDesc('granted_at')
                ->orderByDesc('id');
        }

        $membership->load($relations);

        $person = $membership->person;
        $displayName = trim(
            $person->first_name.' '.
            ($person->name_addition !== null
                ? $person->name_addition.' '
                : '').
            $person->last_name,
        );

        return view('administration.memberships.show', [
            'membership' => $membership,
            'displayName' => $displayName,
            'canManageMemberships' => $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipsManage,
            ),
            'canReadDocuments' => $canReadDocuments,
            'canManageDocuments' => $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipDocumentsManage,
            ),
            'canReadConsents' => $canReadConsents,
            'canManageConsents' => $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipConsentsManage,
            ),
            'canReadPersons' => $access->allowsCapability(
                $actor,
                AdministrationCapability::PersonsRead,
            ),
            'canReadUsers' => $canReadUsers,
            'documentTypes' => MembershipDocumentType::cases(),
            'consentSources' => MembershipConsentSource::cases(),
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Mitgliedschaften',
                    'url' => route('administration.memberships.index'),
                ],
                [
                    'label' => $displayName,
                    'url' => null,
                ],
            ],
        ]);
    }
}
