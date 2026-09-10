<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PersonEditController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
    ): View {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->canManage($actor),
            403,
        );

        $person->load('user');

        $displayName = trim(
            $person->first_name.' '.
            ($person->name_addition !== null
                ? $person->name_addition.' '
                : '').
            $person->last_name,
        );

        return view('administration.persons.edit', [
            'person' => $person,
            'displayName' => $displayName,
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Personen',
                    'url' => route('administration.persons.index'),
                ],
                [
                    'label' => $displayName,
                    'url' => route(
                        'administration.persons.show',
                        $person,
                    ),
                ],
                [
                    'label' => 'Bearbeiten',
                    'url' => null,
                ],
            ],
        ]);
    }
}
