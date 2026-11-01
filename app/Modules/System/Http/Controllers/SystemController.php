<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\System\Models\SystemTaskRun;
use App\Modules\System\Services\SystemChecks;
use App\Modules\System\Services\SystemTasks;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Systemsteuerung (Bereich Admins): Zustand ansehen und Aufgaben aus der festen Liste ausführen. Berechtigung system.manage. */
class SystemController extends Controller
{
    public function __construct(private readonly SystemChecks $checks, private readonly SystemTasks $tasks) {}

    public function status(): View
    {
        $checks = $this->checks->all();

        return view('system.status', [
            'checks' => $checks,
            'counts' => collect($checks)->countBy('status')->all(),
            'pending' => $this->checks->pendingMigrations(),
            'schedulerAge' => $this->checks->schedulerAgeMinutes(),
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    public function tasks(): View
    {
        $last = SystemTaskRun::query()->orderByDesc('id')->limit(300)->get()->unique('task')->keyBy('task');

        return view('system.tasks', [
            'groups' => collect($this->tasks->all())->filter(fn ($task, $key) => $this->tasks->available($key))->groupBy('group'),
            'last' => $last,
            'runs' => SystemTaskRun::query()->with('user.person')->orderByDesc('id')->limit(15)->get(),
            'labels' => collect($this->tasks->all())->map(fn ($task) => $task['label'])->all(),
        ]);
    }

    public function run(Request $request, string $task): RedirectResponse
    {
        abort_if($this->tasks->find($task) === null, 404);

        /** @var User $user */
        $user = Auth::user();
        $key = 'system-task:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['password' => 'Zu viele Versuche. Bitte warten Sie eine Minute.']);
        }

        RateLimiter::hit($key, 60);

        if ($this->tasks->needsPassword($task)) {
            $request->validate(['password' => ['required', 'current_password']], ['password.required' => 'Bitte geben Sie Ihr Passwort ein.', 'password.current_password' => 'Das Passwort ist nicht korrekt.']);
        }

        set_time_limit(180);

        try {
            $run = $this->tasks->run($task, $user);
        } catch (DomainException $exception) {
            return redirect()->route('system.tasks')->with('error', $exception->getMessage());
        }

        $redirect = $task === 'wartung-an' && preg_match('#/([A-Za-z0-9]{24})#', (string) $run->output, $match) === 1
            ? redirect('/'.$match[1])
            : redirect()->route('system.tasks');

        return $run->status === SystemTaskRun::SUCCESS
            ? $redirect->with('success', 'Die Aufgabe „'.$this->tasks->find($task)['label'].'“ wurde ausgeführt.')
            : $redirect->with('error', 'Die Aufgabe „'.$this->tasks->find($task)['label'].'“ ist fehlgeschlagen. Die Ausgabe steht unten bei den letzten Läufen.');
    }
}
