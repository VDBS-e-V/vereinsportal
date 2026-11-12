<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\System\Support\Redactor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Warteschlange (Bereich Admins): fehlgeschlagene Aufträge ansehen, erneut versuchen oder verwerfen. */
class SystemQueueController extends Controller
{
    public function index(): View
    {
        $available = Schema::hasTable('failed_jobs');
        $failed = $available
            ? DB::table('failed_jobs')->orderByDesc('id')->limit(100)->get()->map(function ($row): array {
                $payload = json_decode((string) $row->payload, true) ?: [];
                $exception = (string) $row->exception;

                return [
                    'uuid' => $row->uuid,
                    'job' => $payload['displayName'] ?? ($payload['job'] ?? 'Unbekannt'),
                    'queue' => $row->queue,
                    'failed_at' => $row->failed_at,
                    'message' => Redactor::clean(Str::limit(Str::before($exception, "\n"), 300)),
                    'detail' => Redactor::clean(Str::limit($exception, 3000)),
                ];
            })->all()
            : [];

        return view('system.queue', [
            'available' => $available,
            'failed' => $failed,
            'waiting' => $available && Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
            'driver' => (string) config('queue.default'),
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        if (! $this->exists($uuid)) {
            return redirect()->route('system.queue')->with('error', 'Dieser Auftrag ist nicht mehr in der Liste.');
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);
        $this->log('Auftrag erneut versucht');

        return redirect()->route('system.queue')->with('success', 'Der Auftrag wurde zurück in die Warteschlange gestellt.');
    }

    public function forget(string $uuid): RedirectResponse
    {
        if (! $this->exists($uuid)) {
            return redirect()->route('system.queue')->with('error', 'Dieser Auftrag ist nicht mehr in der Liste.');
        }

        Artisan::call('queue:forget', ['id' => $uuid]);
        $this->log('Auftrag verworfen');

        return redirect()->route('system.queue')->with('success', 'Der Auftrag wurde verworfen.');
    }

    public function flush(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], ['password.required' => 'Bitte geben Sie Ihr Passwort ein.', 'password.current_password' => 'Das Passwort ist nicht korrekt.']);

        Artisan::call('queue:flush');
        $this->log('Alle fehlgeschlagenen Aufträge verworfen');

        return redirect()->route('system.queue')->with('success', 'Alle fehlgeschlagenen Aufträge wurden verworfen.');
    }

    private function exists(string $uuid): bool
    {
        return Schema::hasTable('failed_jobs') && DB::table('failed_jobs')->where('uuid', $uuid)->exists();
    }

    private function log(string $what): void
    {
        /** @var User $user */
        $user = Auth::user();
        AuditLog::record(AuditEvent::SystemTaskRun, $user, null, ['aufgabe' => $what, 'ergebnis' => 'erfolgreich']);
    }
}
