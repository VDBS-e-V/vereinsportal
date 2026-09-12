<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class MembershipEditController extends Controller
{
    public function __invoke(
        Request $request,
        Membership $membership,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::MembershipsManage,
            ),
            403,
        );

        $membership->load('person');
        $person = $membership->person;
        $displayName = trim($person->first_name.' '.$person->last_name);

        return view('administration.memberships.edit', [
            'membership' => $membership,
            'displayName' => $displayName,
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Mitgliedschaften', 'url' => route('administration.memberships.index')],
                ['label' => $displayName, 'url' => route('administration.memberships.show', $membership)],
                ['label' => 'Bearbeiten', 'url' => null],
            ],
        ]);
    }
}
