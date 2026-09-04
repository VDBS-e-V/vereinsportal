<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\TwoFactorRequirement;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RevalidateAuthenticatedUser
{
    private const REVALIDATE_AFTER_SECONDS =
        10 * 60;

    public function __construct(
        private readonly TwoFactorRequirement $twoFactor,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        if (! Auth::check()) {
            return $next($request);
        }

        $authenticated = Auth::user();

        if (! $authenticated instanceof User) {
            return $this->invalidate(
                $request
            );
        }

        $lastValidated =
            (int) $request
                ->session()
                ->get(
                    'identity.account_validated_at',
                    0,
                );

        if (
            $lastValidated > 0
            && $lastValidated
                >= now()
                    ->subSeconds(
                        self::REVALIDATE_AFTER_SECONDS
                    )
                    ->timestamp
        ) {
            return $next($request);
        }

        $freshUser = User::query()
            ->find($authenticated->id);

        if (
            $freshUser === null
            || $freshUser->status
                !== UserStatus::Active
            || $freshUser
                ->email_verified_at === null
            || $freshUser->session_version
                !== (int) $request
                    ->session()
                    ->get(
                        'identity.session_version',
                        0,
                    )
        ) {
            return $this->invalidate(
                $request
            );
        }

        /*
         * Eine Session, die für diesen User einen
         * zweiten Faktor benötigt, muss den erfolgreichen
         * 2FA-Schritt nachweisen.
         */
        if (
            $this->twoFactor
                ->requiresChallenge(
                    $freshUser
                )
            && $request
                ->session()
                ->get(
                    'identity.two_factor_verified_at'
                ) === null
        ) {
            return $this->invalidate(
                $request
            );
        }

        $request->session()->put(
            'identity.account_validated_at',
            now()->timestamp,
        );

        return $next($request);
    }

    private function invalidate(
        Request $request,
    ): RedirectResponse {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()
            ->regenerateToken();

        return redirect()
            ->route('my.login');
    }
}
