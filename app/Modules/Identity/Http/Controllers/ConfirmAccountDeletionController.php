<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\AccountDeletion\ConfirmAccountDeletionWorkflowAction;
use App\Modules\Identity\Exceptions\AccountDeletionCannotConfirm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ConfirmAccountDeletionController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicId,
        ConfirmAccountDeletionWorkflowAction $confirmDeletion,
    ): RedirectResponse {
        try {
            $deletionRequest = $confirmDeletion->execute(
                publicId: $publicId,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (AccountDeletionCannotConfirm) {
            return redirect()
                ->route('my.login')
                ->with(
                    'status',
                    'Dieser Löschlink ist ungültig oder nicht mehr verwendbar.',
                );
        }

        if (Auth::id() === $deletionRequest->user_id) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Auth::forgetUser();
        }

        return redirect()
            ->route('my.login')
            ->with(
                'status',
                'Die Kontolöschung wurde bestätigt. '
                .'Sie kann innerhalb von fünf Tagen widerrufen werden.',
            );
    }
}
