<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Actions\Auth\RequestPasswordResetAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RequestPersonPasswordResetController extends Controller
{
    public function __invoke(
        Request $request,
        Person $person,
        AdministrationAccess $access,
        RequestPasswordResetAction $requestPasswordReset,
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

        if (
            $user === null
            || $user->status !== UserStatus::Active
            || $user->email_verified_at === null
        ) {
            return redirect()
                ->route('administration.persons.index')
                ->with(
                    'status',
                    'Für diese Person ist kein aktives und bestätigtes Portal-Konto verfügbar.',
                )
                ->with('status_type', 'danger');
        }

        $requestPasswordReset->execute(
            email: $user->email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            actor: $actor,
            actorContext: 'administration_person_directory',
        );

        return redirect()
            ->route('administration.persons.index')
            ->with(
                'status',
                'Der Link zum Zurücksetzen des Passworts wurde angefordert.',
            )
            ->with('status_type', 'success');
    }
}
