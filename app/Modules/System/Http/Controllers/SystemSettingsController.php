<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\System\Services\SystemSettings;
use App\Modules\System\Support\Redactor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Einstellungen im Betrieb (Bereich Admins): Werte zum Nachlesen und eine Testmail. Die .env wird nicht vom Portal geschrieben. */
class SystemSettingsController extends Controller
{
    public function __construct(private readonly SystemSettings $settings) {}

    public function index(Request $request): View
    {
        return view('system.settings', [
            'groups' => $this->settings->groups(),
            'mailer' => (string) config('mail.default'),
            'ownEmail' => (string) $request->user()?->email,
        ]);
    }

    public function testMail(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $key = 'system-testmail:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['to' => 'Zu viele Testmails. Bitte warten Sie eine Minute.']);
        }

        RateLimiter::hit($key, 60);

        $data = $request->validate(['to' => ['required', 'email', 'max:190']], ['to.required' => 'Bitte geben Sie eine Empfängeradresse ein.', 'to.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);

        try {
            Mail::raw("Dies ist eine Testmail des VDBS Portals.\nSie wurde von der Systemsteuerung ausgelöst. Wenn Sie sie lesen, funktioniert der E-Mail-Versand.", function ($message) use ($data): void {
                $message->to($data['to'])->subject('Testmail VDBS Portal');
            });
        } catch (Throwable $exception) {
            AuditLog::record(AuditEvent::SystemTaskRun, $user, null, ['aufgabe' => 'Testmail', 'ergebnis' => 'fehlgeschlagen']);

            return redirect()->route('system.settings')->with('error', 'Der Versand ist fehlgeschlagen: '.Redactor::clean($exception->getMessage(), 300));
        }

        AuditLog::record(AuditEvent::SystemTaskRun, $user, null, ['aufgabe' => 'Testmail', 'ergebnis' => 'erfolgreich']);

        $note = in_array((string) config('mail.default'), ['log', 'array'], true) ? ' Der Treiber „'.config('mail.default').'“ stellt nichts zu, die Mail steht nur im Protokoll.' : '';

        return redirect()->route('system.settings')->with('success', 'Die Testmail wurde an '.$data['to'].' übergeben.'.$note);
    }
}
