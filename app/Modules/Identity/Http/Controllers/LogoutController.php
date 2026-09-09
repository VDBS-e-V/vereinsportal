<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\Auth\LogoutAction;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function __invoke(
        Request $request,
        LogoutAction $logout,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()
                ->route('my.login');
        }

        $logout->execute(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()
            ->route('my.login')
            ->with(
                'status',
                'Sie wurden abgemeldet.',
            )
            ->with(
                'status_type',
                'success',
            );
    }
}