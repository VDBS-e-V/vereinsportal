<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\NotificationPreferences;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Konto › Einstellungen: Benachrichtigungen (E-Mail) und Darstellung. */
class AccountSettingsController extends Controller
{
    public function __construct(private readonly NotificationPreferences $preferences) {}

    public function notifications(Request $request): View
    {
        return view('account.notifications', ['groups' => $this->preferences->forUser($this->user($request))]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validate(['notifications' => ['nullable', 'array'], 'notifications.*' => ['string', 'max:60']]);

        $this->preferences->save($user, array_values($data['notifications'] ?? []));

        AuditLog::record(AuditEvent::NotificationsChanged, $user, $user);

        return redirect()->route('account.notifications')->with('success', 'Ihre Einstellungen wurden gespeichert.');
    }

    public function appearance(): View
    {
        return view('account.appearance');
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
