<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailIdentityWriteLock;
use App\Modules\Identity\Support\EmailNormalizer;
use App\Modules\Identity\Support\PasswordRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CompletePortalInvitationAction
{
    public function __construct(
        private readonly EmailIdentityWriteLock $emailWriteLock,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function execute(
        string $publicId,
        int $version,
        string $token,
        string $password,
    ): User {
        Validator::make(
            ['password' => $password],
            ['password' => ['required', PasswordRules::default()]],
        )->validate();

        $invitation = PortalInvitation::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        return $this->emailWriteLock->execute(
            $invitation->email,
            fn (): User => DB::transaction(function () use ($publicId, $version, $token, $password): User {
                $lockedInvitation = PortalInvitation::query()
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $lockedInvitation->isOpen()
                    || $lockedInvitation->token_version !== $version
                    || ! hash_equals($lockedInvitation->token_hash, hash('sha256', $token))
                ) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Diese Einladung ist nicht mehr gültig.',
                    ]);
                }

                $person = Person::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedInvitation->person_id);

                $email = EmailNormalizer::normalize((string) $person->email);

                if ($email !== $lockedInvitation->email) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Die E-Mail-Adresse der Person wurde seit der Einladung geändert.',
                    ]);
                }

                if ($person->user()->exists() || User::query()->where('email', $email)->exists()) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Für diese Person oder E-Mail-Adresse existiert bereits ein Portalzugang.',
                    ]);
                }

                $user = User::query()->create([
                    'person_id' => $person->id,
                    'email' => $email,
                    'password' => $password,
                    'status' => UserStatus::Active,
                    'session_version' => 1,
                ]);
                $user->email_verified_at = now();
                $user->save();

                $acceptedAt = now();
                $lockedInvitation->forceFill([
                    'accepted_at' => $acceptedAt,
                ])->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::PORTAL_INVITATION_ACCEPTED,
                    actorType: AuditActorType::User,
                    actorUserId: $user->id,
                    subjectType: 'portal_invitation',
                    subjectId: $lockedInvitation->id,
                    newValues: [
                        'person_id' => $person->id,
                        'user_id' => $user->id,
                        'accepted_at' => $acceptedAt->toIso8601String(),
                    ],
                );

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::ACCOUNT_REGISTERED,
                    actorType: AuditActorType::User,
                    actorUserId: $user->id,
                    subjectType: 'user',
                    subjectId: $user->id,
                    newValues: [
                        'linkage_type' => 'portal_invitation',
                    ],
                );

                return $user;
            }),
        );
    }
}
