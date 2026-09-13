<?php

namespace App\Modules\Identity\Actions\Profile;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class StoreProfileAvatarAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        User $user,
        UploadedFile $avatar,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): User {
        Validator::make(
            ['avatar' => $avatar],
            [
                'avatar' => [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],
            ],
        )->validate();

        $extension = strtolower(
            $avatar->guessExtension()
                ?? $avatar->extension()
                ?? 'bin',
        );

        $newPath = Storage::disk('local')->putFileAs(
            'profile-avatars/'.$user->id,
            $avatar,
            Str::uuid()->toString().'.'.$extension,
        );

        if (! is_string($newPath) || $newPath === '') {
            throw new RuntimeException('Profilbild konnte nicht gespeichert werden.');
        }

        $oldPath = null;

        try {
            DB::transaction(function () use (
                $user,
                $avatar,
                $newPath,
                $ipAddress,
                $userAgent,
                &$oldPath,
            ): void {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldPath = $lockedUser->avatar_path;
                $lockedUser->avatar_path = $newPath;
                $lockedUser->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::ACCOUNT_AVATAR_UPDATED,
                    actorType: AuditActorType::User,
                    actorUserId: $lockedUser->id,
                    subjectType: 'user',
                    subjectId: $lockedUser->id,
                    newValues: [
                        'mime_type' => $avatar->getMimeType(),
                        'size_bytes' => $avatar->getSize(),
                    ],
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $user->refresh();
    }
}
