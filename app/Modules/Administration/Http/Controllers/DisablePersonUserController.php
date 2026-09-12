<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\DisableUserAction;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DisablePersonUserController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
        DisableUserAction $disableUser,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::UserStatusManage,
            ),
            403,
        );

        $person->loadMissing('user');
        $user = $person->user;

        if ($user === null) {
            return redirect()
                ->route('administration.persons.index')
                ->with(
                    'status',
                    'Für diese Person ist kein Portal-Konto vorhanden.',
                )
                ->with('status_type', 'danger');
        }

        try {
            $disableUser->execute(
                target: $user,
                actor: $actor,
                comment: 'Konto über die Personenliste gesperrt.',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (AdministrationActionRejected $exception) {
            return redirect()
                ->route('administration.persons.index')
                ->with('status', $exception->getMessage())
                ->with('status_type', 'danger');
        }

        return redirect()
            ->route('administration.persons.index')
            ->with('status', 'Das Konto wurde gesperrt.')
            ->with('status_type', 'success');
    }
}
