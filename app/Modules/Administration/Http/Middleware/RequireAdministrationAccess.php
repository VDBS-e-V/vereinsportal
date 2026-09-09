<?php

namespace App\Modules\Administration\Http\Middleware;

use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdministrationAccess
{
    public function __construct(
        private readonly AdministrationAccess $access,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (
            ! $user instanceof User
            || ! $this->access->allows($user)
        ) {
            abort(403);
        }

        return $next($request);
    }
}
