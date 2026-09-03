<?php

namespace App\Modules\Identity\Actions\Auth;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Auth;

final class LogoutAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): void {
        $this->auditWriter->write(
            eventKey: AuditEventCatalog::AUTH_LOGOUT,
            actorType: AuditActorType::User,
            actorUserId: $user->id,
            subjectType: 'user',
            subjectId: $user->id,
            newValues: [
                'scope' => 'current_session',
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceInfo: $deviceInfo,
        );

        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();
    }
}