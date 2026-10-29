<?php

namespace App\Modules\AccessGovernance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Services\RoleAssignments;
use App\Modules\AccessGovernance\Support\Capabilities;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Rollen eines Nutzers vergeben und beenden. Die Rolle Administration vergibt nur die Administration. */
class RoleAssignmentController extends Controller
{
    public function __construct(private readonly RoleAssignments $roles) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        $actor = $this->actor($request);

        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'ends_at' => ['nullable', 'date', 'after:today'],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'role_id.required' => 'Bitte wählen Sie eine Rolle.',
            'role_id.exists' => 'Diese Rolle gibt es nicht.',
            'ends_at.after' => 'Das Enddatum muss in der Zukunft liegen.',
        ]);

        $role = Role::query()->findOrFail($data['role_id']);
        $key = RoleKey::tryFrom($role->key);

        abort_if($key === null || ! Capabilities::canAssign($actor, $key), 403);

        try {
            $this->roles->assign($user, $role, $actor, filled($data['ends_at'] ?? null) ? Carbon::parse($data['ends_at'])->endOfDay() : null, $data['comment'] ?? null);
        } catch (DomainException $exception) {
            return redirect()->route('admin.users.show', $user)->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Die Rolle „'.$role->name.'“ wurde vergeben.');
    }

    public function destroy(Request $request, User $user, RoleAssignment $assignment): RedirectResponse
    {
        $actor = $this->actor($request);

        abort_unless($assignment->user_id === $user->id, 404);

        $key = RoleKey::tryFrom((string) $assignment->role?->key);

        abort_if($key === null || ! Capabilities::canAssign($actor, $key), 403);

        try {
            $this->roles->end($assignment);
        } catch (DomainException $exception) {
            return redirect()->route('admin.users.show', $user)->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Die Rolle wurde beendet.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
