<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\CreatePersonAction;
use App\Modules\Administration\Exceptions\PossiblePersonDuplicate;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StorePersonController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationAccess $access,
        CreatePersonAction $createPerson,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->canManage($actor),
            403,
        );

        try {
            $person = $createPerson->execute(
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
                allowPossibleDuplicate: $request->boolean(
                    'confirm_possible_duplicate',
                ),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (PossiblePersonDuplicate $exception) {
            return redirect()
                ->route('administration.persons.create')
                ->withInput()
                ->with(
                    'possible_person_match_ids',
                    $exception->personIds,
                )
                ->with(
                    'status',
                    'Es wurden mögliche vorhandene Personen gefunden. Prüfen Sie die Treffer, bevor Sie den Datensatz bewusst trotzdem anlegen.',
                )
                ->with(
                    'status_type',
                    'warning',
                );
        }

        return redirect()
            ->route(
                'administration.persons.show',
                $person,
            )
            ->with(
                'status',
                'Die Person wurde angelegt.',
            )
            ->with(
                'status_type',
                'success',
            );
    }
}
