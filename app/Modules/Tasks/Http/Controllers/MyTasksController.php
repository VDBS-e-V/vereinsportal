<?php

namespace App\Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Services\ProjectAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** „Meine Aufgaben“: alle Aufgaben, die dem eigenen Konto zugewiesen sind, über alle Projekte, die man sehen darf. */
class MyTasksController extends Controller
{
    public function __construct(private readonly ProjectAccess $access) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->validate(['status' => ['nullable', Rule::in(['open', 'done', 'all'])]]);
        $status = $filters['status'] ?? 'open';

        $tasks = Task::query()->with(['project', 'status', 'assignees.person'])
            ->whereIn('task_project_id', $this->access->visible($user)->select('task_projects.id'))
            ->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $user->id))
            ->when($status === 'open', fn ($query) => $query->whereNull('done_at'))
            ->when($status === 'done', fn ($query) => $query->whereNotNull('done_at'))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereNull('done_at')->whereDate('due_on', '<', today()))
            ->orderByRaw('done_at is not null')->orderByRaw('due_on is null')->orderBy('due_on')->orderBy('id')->limit(300)->get();

        return view('tasks.mine', ['tasks' => $tasks, 'status' => $status, 'overdue' => $request->boolean('overdue')]);
    }
}
