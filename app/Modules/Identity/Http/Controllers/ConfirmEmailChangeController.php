<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\EmailChange\CompleteEmailChangeWorkflowAction;
use App\Modules\Identity\Exceptions\EmailChangeCannotComplete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ConfirmEmailChangeController
{
    public function __invoke(
        Request $request,
        CompleteEmailChangeWorkflowAction $complete,
        string $publicId,
    ): RedirectResponse {
        try {
            $emailChange = $complete->execute(
                publicId: $publicId,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (EmailChangeCannotComplete) {
            return redirect()
                ->route('my.login')
                ->with(
                    'status',
                    'Die E-Mail-Änderung konnte nicht abgeschlossen werden.'
                );
        }

        /*
         * Nur die Session des tatsächlich betroffenen Accounts
         * explizit beenden. Ein zufällig parallel angemeldeter
         * anderer Account wird nicht ausgeloggt.
         */
        if (Auth::id() === $emailChange->user_id) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('my.login')
            ->with(
                'status',
                'Ihre neue E-Mail-Adresse wurde bestätigt. Bitte melden Sie sich erneut an.'
            );
    }
}