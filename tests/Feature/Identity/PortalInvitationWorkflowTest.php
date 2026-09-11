<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\PortalInvitation\CompletePortalInvitationAction;
use App\Modules\Identity\Actions\PortalInvitation\ResendPortalInvitationAction;
use App\Modules\Identity\Actions\PortalInvitation\RevokePortalInvitationAction;
use App\Modules\Identity\Actions\PortalInvitation\StartPortalInvitationAction;
use App\Modules\Identity\Enums\PortalInvitationStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PortalInvitation;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function portalInvitationActor(string $email = 'invitation-admin@example.test'): User
{
    $user = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function portalInvitationPerson(string $email): Person
{
    return Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-04-12',
        'email' => $email,
        'country_code' => 'DE',
    ]);
}

it('stores only the invitation token hash and does not leak the token to audit values', function () {
    $actor = portalInvitationActor();
    $person = portalInvitationPerson('invited-hash@example.test');

    $result = app(StartPortalInvitationAction::class)->execute($person, $actor);
    $invitation = $result['invitation'];
    $token = $result['token'];

    expect($token)->not->toBe('')
        ->and($invitation->token_hash)->toBe(hash('sha256', $token))
        ->and($invitation->token_hash)->not->toBe($token)
        ->and($invitation->status())->toBe(PortalInvitationStatus::Open)
        ->and(User::query()->where('person_id', $person->id)->exists())->toBeFalse();

    $audit = AuditEvent::query()
        ->where('event_key', AuditEventCatalog::PORTAL_INVITATION_CREATED)
        ->where('subject_id', $invitation->id)
        ->firstOrFail();

    expect(json_encode($audit->new_values, JSON_THROW_ON_ERROR))
        ->not->toContain($token)
        ->and($audit->new_values['person_id'] ?? null)->toBe($person->id)
        ->and($audit->new_values['email'] ?? null)->toBe('invited-hash@example.test');
});

it('rotates invitation credentials on resend and accepts only the newest token once', function () {
    $actor = portalInvitationActor('invitation-resend-admin@example.test');
    $person = portalInvitationPerson('invited-resend@example.test');

    $started = app(StartPortalInvitationAction::class)->execute($person, $actor);
    $originalInvitation = $started['invitation'];
    $originalToken = $started['token'];
    $originalHash = $originalInvitation->token_hash;

    $resent = app(ResendPortalInvitationAction::class)->execute($originalInvitation, $actor);
    $invitation = $resent['invitation'];
    $newToken = $resent['token'];

    expect($invitation->token_version)->toBe(2)
        ->and($invitation->token_hash)->toBe(hash('sha256', $newToken))
        ->and($invitation->token_hash)->not->toBe($originalHash)
        ->and($newToken)->not->toBe($originalToken);

    expect(fn () => app(CompletePortalInvitationAction::class)->execute(
        $invitation->public_id,
        1,
        $originalToken,
        'Invitation123!',
    ))->toThrow(ValidationException::class);

    $user = app(CompletePortalInvitationAction::class)->execute(
        $invitation->public_id,
        2,
        $newToken,
        'Invitation123!',
    );

    expect($user->person_id)->toBe($person->id)
        ->and($user->email)->toBe('invited-resend@example.test')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Invitation123!', $user->password))->toBeTrue()
        ->and($invitation->refresh()->status())->toBe(PortalInvitationStatus::Accepted)
        ->and(Person::query()->count())->toBe(1);

    expect(fn () => app(CompletePortalInvitationAction::class)->execute(
        $invitation->public_id,
        2,
        $newToken,
        'Invitation123!',
    ))->toThrow(ValidationException::class);
});

it('rejects expired and revoked invitations', function () {
    $actor = portalInvitationActor('invitation-invalid-admin@example.test');

    $expiredPerson = portalInvitationPerson('invited-expired@example.test');
    $expired = app(StartPortalInvitationAction::class)->execute($expiredPerson, $actor);
    $expired['invitation']->forceFill(['expires_at' => now()->subSecond()])->save();

    expect(fn () => app(CompletePortalInvitationAction::class)->execute(
        $expired['invitation']->public_id,
        $expired['invitation']->token_version,
        $expired['token'],
        'Invitation123!',
    ))->toThrow(ValidationException::class);

    $revokedPerson = portalInvitationPerson('invited-revoked@example.test');
    $revoked = app(StartPortalInvitationAction::class)->execute($revokedPerson, $actor);
    app(RevokePortalInvitationAction::class)->execute($revoked['invitation'], $actor);

    expect($revoked['invitation']->refresh()->status())->toBe(PortalInvitationStatus::Revoked);

    expect(fn () => app(CompletePortalInvitationAction::class)->execute(
        $revoked['invitation']->public_id,
        $revoked['invitation']->token_version,
        $revoked['token'],
        'Invitation123!',
    ))->toThrow(ValidationException::class);

    expect(User::query()->whereIn('person_id', [$expiredPerson->id, $revokedPerson->id])->exists())
        ->toBeFalse();
});

it('rejects an invitation when the persons email changed after invitation creation', function () {
    $actor = portalInvitationActor('invitation-email-admin@example.test');
    $person = portalInvitationPerson('invited-before@example.test');
    $started = app(StartPortalInvitationAction::class)->execute($person, $actor);

    $person->forceFill(['email' => 'invited-after@example.test'])->save();

    expect(fn () => app(CompletePortalInvitationAction::class)->execute(
        $started['invitation']->public_id,
        $started['invitation']->token_version,
        $started['token'],
        'Invitation123!',
    ))->toThrow(ValidationException::class);

    expect(User::query()->where('person_id', $person->id)->exists())->toBeFalse()
        ->and(PortalInvitation::query()->findOrFail($started['invitation']->id)->accepted_at)->toBeNull();
});
