<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
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
        $membership->load('person.user');
        $actor = $request->user();
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
            'canManage' => $actor instanceof User
                && $access->canManage($actor),
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
