<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PersonShowController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
    ): View {
        $person->load('user');
        $memberships = $person->memberships()
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
        $portalInvitations = $person->portalInvitations()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $displayName = trim(
            $person->first_name.' '.
            ($person->name_addition !== null
                ? $person->name_addition.' '
                : '').
            $person->last_name,
        );

        $actor = $request->user();

        return view('administration.persons.show', [
            'person' => $person,
            'memberships' => $memberships,
            'portalInvitations' => $portalInvitations,
            'displayName' => $displayName,
            'canManage' => $actor instanceof User
                && $access->canManage($actor),
            'breadcrumbs' => [
                ['label' => 'Verwaltung', 'url' => route('administration.home')],
                ['label' => 'Personen', 'url' => route('administration.persons.index')],
                ['label' => $displayName, 'url' => null],
            ],
        ]);
    }
}
