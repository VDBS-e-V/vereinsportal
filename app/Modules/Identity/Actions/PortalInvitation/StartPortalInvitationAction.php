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

final class StartPortalInvitationAction
{
    public function __construct(
        private readonly EmailIdentityWriteLock $emailWriteLock,
        private readonly AuditWriter $auditWriter,
    ) {}

    /** @return array{invitation: PortalInvitation, token: string} */
    public function execute(Person $person, User $actor): array
    {
        $email = EmailNormalizer::normalize((string) $person->email);

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Für die Einladung ist eine E-Mail-Adresse an der Person erforderlich.',
            ]);
        }

        return $this->emailWriteLock->execute(
            $email,
            fn (): array => DB::transaction(function () use ($person, $actor, $email): array {
                $lockedPerson = Person::query()
                    ->lockForUpdate()
                    ->findOrFail($person->id);

                if ($lockedPerson->user()->exists()) {
                    throw ValidationException::withMessages([
                        'person' => 'Für diese Person existiert bereits ein Portalzugang.',
                    ]);
                }

                if (EmailNormalizer::normalize((string) $lockedPerson->email) !== $email) {
                    throw ValidationException::withMessages([
                        'email' => 'Die E-Mail-Adresse der Person wurde parallel geändert. Bitte erneut versuchen.',
                    ]);
                }

                if (User::query()->where('email', $email)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Diese E-Mail-Adresse wird bereits von einem Portalzugang verwendet.',
                    ]);
                }

                $hasOpenInvitation = PortalInvitation::query()
                    ->where('person_id', $lockedPerson->id)
                    ->whereNull('accepted_at')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->exists();

                if ($hasOpenInvitation) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Für diese Person besteht bereits eine offene Einladung.',
                    ]);
                }

                $token = Str::random(64);
                $expiresAt = now()->addDays(3);

                $invitation = PortalInvitation::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'person_id' => $lockedPerson->id,
                    'email' => $email,
                    'token_hash' => hash('sha256', $token),
                    'token_version' => 1,
                    'expires_at' => $expiresAt,
                    'created_by_user_id' => $actor->id,
                ]);

                $this->auditWriter->write(
                    eventKey: AuditEventCatalog::PORTAL_INVITATION_CREATED,
                    actorType: AuditActorType::User,
                    actorUserId: $actor->id,
                    subjectType: 'portal_invitation',
                    subjectId: $invitation->id,
                    newValues: [
                        'person_id' => $lockedPerson->id,
                        'email' => $email,
                        'expires_at' => $expiresAt->toIso8601String(),
                    ],
                );

                return [
                    'invitation' => $invitation,
                    'token' => $token,
                ];
            }),
        );
    }
}
