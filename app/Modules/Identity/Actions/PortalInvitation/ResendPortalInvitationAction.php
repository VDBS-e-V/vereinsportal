<?php

namespace App\Modules\Identity\Actions\PortalInvitation;

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Services\AuditWriter;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\EmailIdentityWriteLock;
use App\Modules\Identity\Support\EmailNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResendPortalInvitationAction
{
    public function __construct(
        private readonly EmailIdentityWriteLock $emailWriteLock,
        private readonly AuditWriter $auditWriter,
    ) {}

    /** @return array{invitation: PortalInvitation, token: string} */
    public function execute(PortalInvitation $invitation, User $actor): array
    {
        $person = $invitation->person()->firstOrFail();
        $email = EmailNormalizer::normalize((string) $person->email);

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Für den erneuten Versand ist eine E-Mail-Adresse erforderlich.',
            ]);
        }

        return $this->emailWriteLock->execute(
            $email,
            fn (): array => DB::transaction(function () use ($invitation, $actor, $email): array {
                $lockedInvitation = PortalInvitation::query()
                    ->lockForUpdate()
                    ->findOrFail($invitation->id);
                $person = Person::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedInvitation->person_id);

                if ($lockedInvitation->accepted_at !== null || $lockedInvitation->revoked_at !== null) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Diese Einladung kann nicht erneut versendet werden.',
                    ]);
                }

                if ($person->user()->exists() || User::query()->where('email', $email)->exists()) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Für diese Person oder E-Mail-Adresse existiert bereits ein Portalzugang.',
                    ]);
                }

                if (EmailNormalizer::normalize((string) $person->email) !== $email) {
                    throw ValidationException::withMessages([
                        'email' => 'Die E-Mail-Adresse wurde parallel geändert. Bitte erneut versuchen.',
                    ]);
                }

                $token = Str::random(64);
                $expiresAt = now()->addDays(3);
                $tokenVersion = $lockedInvitation->token_version + 1;

                $lockedInvitation->forceFill([
                    'email' => $email,
                    'token_hash' => hash('sha256', $token),
                    'token_version' => $tokenVersion,
                    'expires_at' => $expiresAt,
                    'sent_at' => null,
                ])->save();

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::PORTAL_INVITATION_RESENT,
                    actorType: AuditActorType::User,
                    actorUserId: $actor->id,
                    subjectType: 'portal_invitation',
                    subjectId: $lockedInvitation->id,
                    newValues: [
                        'email' => $email,
                        'expires_at' => $expiresAt->toIso8601String(),
                        'token_version' => $tokenVersion,
                    ],
                );

                return [
                    'invitation' => $lockedInvitation->refresh(),
                    'token' => $token,
                ];
            }),
        );
    }
}
