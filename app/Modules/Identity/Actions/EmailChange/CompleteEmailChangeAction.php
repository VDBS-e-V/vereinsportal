<?php

namespace App\Modules\Identity\Actions\EmailChange;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\EmailChangeCannotComplete;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CompleteEmailChangeAction
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
    }

    public function execute(
        string $publicId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $deviceInfo = null,
    ): EmailChangeRequest {
        try {
            return DB::transaction(
                function () use (
                    $publicId,
                    $ipAddress,
                    $userAgent,
                    $deviceInfo,
                ): EmailChangeRequest {
                    $request =
                        EmailChangeRequest::query()
                            ->where(
                                'public_id',
                                $publicId,
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        $request === null
                        || $request->status
                            !== EmailChangeRequestStatus::Pending
                        || $request->expires_at->isPast()
                    ) {
                        throw EmailChangeCannotComplete::
                            invalidOrExpired();
                    }

                    $user = User::query()
                        ->whereKey(
                            $request->user_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $user->status
                            !== UserStatus::Active
                        || $user->person_id === null
                    ) {
                        throw EmailChangeCannotComplete::
                            invalidOrExpired();
                    }

                    $person = Person::query()
                        ->whereKey($user->person_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $currentPersonEmail =
                        EmailNormalizer::normalize(
                            $person->email
                        );

                    $currentUserEmail =
                        EmailNormalizer::normalize(
                            $user->email
                        );

                    $expectedOldEmail =
                        EmailNormalizer::normalize(
                            $request->old_email
                        );

                    if (
                        $currentPersonEmail
                            !== $expectedOldEmail
                        || $currentUserEmail
                            !== $expectedOldEmail
                    ) {
                        throw EmailChangeCannotComplete::
                            invalidOrExpired();
                    }

                    $newEmail =
                        EmailNormalizer::normalize(
                            $request->new_email
                        );

                    if (
                        Person::query()
                            ->where(
                                'email',
                                $newEmail,
                            )
                            ->where(
                                'id',
                                '<>',
                                $person->id,
                            )
                            ->exists()
                        || User::query()
                            ->where(
                                'email',
                                $newEmail,
                            )
                            ->where(
                                'id',
                                '<>',
                                $user->id,
                            )
                            ->exists()
                    ) {
                        throw EmailChangeCannotComplete::
                            emailUnavailable();
                    }

                    $person->email = $newEmail;
                    $person->save();

                    $user->email = $newEmail;

                    /*
                     * Der neue Identifier wurde gerade
                     * erfolgreich über den signierten Link
                     * bestätigt.
                     */
                    $user->email_verified_at =
                        now();

                    $user->remember_token = null;
                    $user->session_version++;
                    $user->save();

                    $request->status =
                        EmailChangeRequestStatus::
                            Confirmed;

                    $request->confirmed_at = now();
                    $request->save();

                    DB::table(
                        config(
                            'session.table',
                            'sessions',
                        )
                    )
                        ->where(
                            'user_id',
                            $user->id,
                        )
                        ->delete();

                    $this->auditWriter->write(
                        eventKey:
                            AuditEventCatalog::
                                AUTH_EMAIL_CHANGE_COMPLETED,
                        actorType:
                            AuditActorType::User,
                        actorUserId:
                            $user->id,
                        subjectType: 'user',
                        subjectId: $user->id,
                        newValues: [
                            'old_email' =>
                                $expectedOldEmail,
                            'new_email' =>
                                $newEmail,
                        ],
                        ipAddress: $ipAddress,
                        userAgent: $userAgent,
                        deviceInfo: $deviceInfo,
                    );

                    $this->auditWriter->write(
                        eventKey:
                            AuditEventCatalog::
                                AUTH_SESSIONS_INVALIDATED,
                        actorType:
                            AuditActorType::User,
                        actorUserId:
                            $user->id,
                        subjectType: 'user',
                        subjectId: $user->id,
                        newValues: [
                            'reason' =>
                                'email_change',
                        ],
                        ipAddress: $ipAddress,
                        userAgent: $userAgent,
                        deviceInfo: $deviceInfo,
                    );

                    return $request->refresh();
                },
            );
        } catch (QueryException $exception) {
            if (
                (string) $exception->getCode()
                === '23000'
            ) {
                throw EmailChangeCannotComplete::
                    emailUnavailable();
            }

            throw $exception;
        }
    }
}