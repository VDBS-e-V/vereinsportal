<?php

namespace App\Modules\Identity\Actions\Profile;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteProfileAvatarAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): User {
        $oldPath = null;

        DB::transaction(function () use (
            $user,
            $ipAddress,
            $userAgent,
            &$oldPath,
        ): void {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldPath = $lockedUser->avatar_path;

            if (! is_string($oldPath) || $oldPath === '') {
                return;
            }

            $lockedUser->avatar_path = null;
            $lockedUser->save();

            $this->auditWriter->write(
                eventKey: AuditEventCatalog::ACCOUNT_AVATAR_REMOVED,
                actorType: AuditActorType::User,
                actorUserId: $lockedUser->id,
                subjectType: 'user',
                subjectId: $lockedUser->id,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );
        });

        if (is_string($oldPath) && $oldPath !== '') {
            Storage::disk('local')->delete($oldPath);
        }

        return $user->refresh();
    }
}
