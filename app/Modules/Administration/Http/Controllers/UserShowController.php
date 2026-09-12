<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;

final class UserShowController extends Controller
{
    public function __invoke(
        User $user,
        AdministrationAccess $access,
    ): View {
        $user->load([
            'person',
            'roleAssignments' => fn ($query) => $query
                ->with([
                    'role',
                    'grantedBy',
                ])
                ->orderByDesc('starts_at'),
        ]);

        $displayName = trim(
            ($user->person->first_name ?? '').' '.
            ($user->person->last_name ?? '')
        );

        if ($displayName === '') {
            $displayName = $user->email;
        }

        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $canManageStatus = $access->allowsCapability(
            $actor,
            AdministrationCapability::UserStatusManage,
        );
        $canManageRoles = $access->allowsCapability(
            $actor,
            AdministrationCapability::RolesManage,
        );

        return view('administration.users.show', [
            'user' => $user,
            'displayName' => $displayName,
            'canManageStatus' => $canManageStatus,
            'canManageRoles' => $canManageRoles,
            'availableRoles' => $canManageRoles
                ? Role::query()
                    ->orderBy('name')
                    ->get()
                : collect(),
            'breadcrumbs' => [
                [
                    'label' => 'Verwaltung',
                    'url' => route('administration.home'),
                ],
                [
                    'label' => 'Benutzer',
                    'url' => route('administration.users.index'),
                ],
                [
                    'label' => $displayName,
                    'url' => null,
                ],
            ],
        ]);
    }
}
