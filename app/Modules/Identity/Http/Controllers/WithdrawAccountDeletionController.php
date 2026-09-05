<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\AccountDeletion\WithdrawAccountDeletionAction;
use App\Modules\Identity\Exceptions\AccountDeletionCannotWithdraw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WithdrawAccountDeletionController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicId,
        WithdrawAccountDeletionAction $withdrawDeletion,
    ): RedirectResponse {
        try {
            $withdrawDeletion->execute(
                publicId: $publicId,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (AccountDeletionCannotWithdraw) {
            return redirect()
                ->route('my.login')
                ->with(
                    'status',
                    'Dieser Widerrufslink ist ungültig oder nicht mehr verwendbar.',
                );
        }

        return redirect()
            ->route('my.login')
            ->with(
                'status',
                'Die Kontolöschung wurde widerrufen. '
                .'Bitte melden Sie sich erneut an.',
            );
    }
}
