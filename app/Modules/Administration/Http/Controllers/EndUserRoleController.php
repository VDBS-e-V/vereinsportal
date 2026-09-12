<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\EndManualRoleAssignmentAction;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EndUserRoleController extends Controller
{
    public function __invoke(
        Request $request,
        User $user,
        RoleAssignment $assignment,
        AdministrationAccess $access,
        EndManualRoleAssignmentAction $end,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User
            && $access->allowsCapability(
                $actor,
                AdministrationCapability::RolesManage,
            ),
            403,
        );

        $validated = $request->validate([
            'end_comment' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            $end->execute(
                target: $user,
                assignment: $assignment,
                actor: $actor,
                comment: $validated['end_comment'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (AdministrationActionRejected $exception) {
            return redirect()
                ->route(
                    'administration.users.show',
                    $user,
                )
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
                'Die Rollenzuweisung wurde beendet.',
            )
            ->with(
                'status_type',
                'success',
            );
    }
}
