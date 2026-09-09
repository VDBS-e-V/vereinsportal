<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\DisableUserAction;
use App\Modules\Administration\Actions\ReactivateUserAction;
use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UpdateUserStatusController extends Controller
{
    public function __invoke(
        Request $request,
        User $user,
        AdministrationAccess $access,
        DisableUserAction $disable,
        ReactivateUserAction $reactivate,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->canManage($actor),
            403,
        );

        $validated = $request->validate([
            'status_action' => [
                'required',
                Rule::in([
                    'disable',
                    'reactivate',
                ]),
            ],
            'status_comment' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            if ($validated['status_action'] === 'disable') {
                $disable->execute(
                    target: $user,
                    actor: $actor,
                    comment: $validated['status_comment'],
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );

                $message = 'Das Konto wurde deaktiviert.';
            } else {
                $reactivate->execute(
                    target: $user,
                    actor: $actor,
                    comment: $validated['status_comment'],
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );

                $message = 'Das Konto wurde reaktiviert.';
            }
        } catch (AdministrationActionRejected $exception) {
            return redirect()
                ->route(
                    'administration.users.show',
                    $user,
                )
                ->withInput()
                ->with(
                    'status',
                    $exception->getMessage(),
                )
                ->with(
                    'status_type',
                    'danger',
                );
        }

        return redirect()
            ->route(
                'administration.users.show',
                $user,
            )
            ->with(
                'status',
                $message,
            )
            ->with(
                'status_type',
                'success',
            );
    }
}
