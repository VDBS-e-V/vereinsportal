<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Actions\AssignManualRoleAction;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Exceptions\AdministrationActionRejected;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AssignUserRoleController extends Controller
{
    public function __invoke(
        Request $request,
        User $user,
        AdministrationAccess $access,
        AssignManualRoleAction $assign,
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
            'role_key' => [
                'required',
                Rule::enum(RoleKey::class),
            ],
            'role_comment' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            $assign->execute(
                target: $user,
                actor: $actor,
                roleKey: RoleKey::from(
                    $validated['role_key']
                ),
                comment: $validated['role_comment'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
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
                'Die Rolle wurde zugewiesen.',
            )
            ->with(
                'status_type',
                'success',
            );
    }
}
