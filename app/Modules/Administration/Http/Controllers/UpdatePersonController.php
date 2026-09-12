<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\UpdatePersonAction;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UpdatePersonController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
        UpdatePersonAction $updatePerson,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::PersonsManage,
            ),
            403,
        );

        $person = $updatePerson->execute(
            person: $person,
            values: $request->only([
                'title',
                'first_name',
                'name_addition',
                'last_name',
                'birth_date',
                'email',
                'phone',
                'street',
                'house_number',
                'postal_code',
                'city',
                'country_code',
            ]),
            actor: $actor,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route(
                'administration.persons.show',
                $person,
            )
            ->with(
                'status',
                'Die Personendaten wurden gespeichert.',
            )
            ->with(
                'status_type',
                'success',
            );
    }
}
