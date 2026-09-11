<?php

namespace App\Modules\Administration\Http\Middleware;

use App\Modules\Administration\Enums\AdministrationCapability;
use App\Modules\Administration\Support\AdministrationAccess;
use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdministrationCapability
{
    public function __construct(
        private readonly AdministrationAccess $access,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $capabilityValue,
    ): Response {
        $capability = AdministrationCapability::tryFrom($capabilityValue);

        abort_if($capability === null, 500, 'Unknown administration capability.');

        $user = $request->user();

        if (
            ! $user instanceof User
            || ! $this->access->allowsCapability($user, $capability)
        ) {
            abort(403);
        }

        return $next($request);
    }
}
