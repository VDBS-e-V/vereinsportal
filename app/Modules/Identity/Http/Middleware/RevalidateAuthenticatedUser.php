<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RevalidateAuthenticatedUser
{
    private const REVALIDATE_AFTER_SECONDS = 600;

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        if (! Auth::check()) {
            return $next($request);
        }

        $authenticatedUser = Auth::user();

        if (! $authenticatedUser instanceof User) {
            $this->invalidate($request);

            return redirect()
                ->route('my.login');
        }

        $validatedAt = (int) $request->session()->get(
            'identity.account_validated_at',
            0,
        );

        if (
            $validatedAt > 0
            && now()->timestamp - $validatedAt
                < self::REVALIDATE_AFTER_SECONDS
        ) {
            return $next($request);
        }

        $user = User::query()
            ->whereKey($authenticatedUser->id)
            ->first();

        $expectedSessionVersion = (int) $request
            ->session()
            ->get(
                'identity.session_version',
                0,
            );

        if (
            $user === null
            || $user->status !== UserStatus::Active
            || $user->email_verified_at === null
            || $expectedSessionVersion !== $user->session_version
        ) {
            $this->invalidate($request);

            return redirect()
                ->route('my.login');
        }

        $request->session()->put(
            'identity.account_validated_at',
            now()->timestamp,
        );

        return $next($request);
    }

    private function invalidate(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}