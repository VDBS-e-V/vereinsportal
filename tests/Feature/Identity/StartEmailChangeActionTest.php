<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\EmailChange\StartEmailChangeAction;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\EmailChangeCannotStart;
use App\Modules\Identity\Models\EmailChangeRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;

function makeEmailChangeStartUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'old@example.test',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => 'old@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('starts an email change without changing the current email', function () {
    $user = makeEmailChangeStartUser();

    $request = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: '  NEW@EXAMPLE.TEST  ',
        ipAddress: '127.0.0.1',
    );

    $user->refresh();
    $user->person->refresh();

    expect($request->new_email)
        ->toBe('new@example.test')
        ->and($request->old_email)
        ->toBe('old@example.test')
        ->and($request->status)
        ->toBe(EmailChangeRequestStatus::Pending)
        ->and($user->email)
        ->toBe('old@example.test')
        ->and($user->person->email)
        ->toBe('old@example.test')
        ->and(
            $request->expires_at
                ->greaterThan(
                    now()->addHours(71)
                )
        )
        ->toBeTrue()
        ->and(
            $request->expires_at
                ->lessThan(
                    now()->addHours(73)
                )
        )
        ->toBeTrue()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_EMAIL_CHANGE_REQUESTED,
                )
                ->count()
        )
        ->toBe(1);
});

it('supersedes the previous open email change', function () {
    $user = makeEmailChangeStartUser();

    $action = app(
        StartEmailChangeAction::class
    );

    $first = $action->execute(
        user: $user,
        newEmail: 'first@example.test',
    );

    $second = $action->execute(
        user: $user,
        newEmail: 'second@example.test',
    );

    $first->refresh();
    $second->refresh();

    expect($first->status)
        ->toBe(
            EmailChangeRequestStatus::Superseded
        )
        ->and($first->superseded_at)
        ->not->toBeNull()
        ->and($second->status)
        ->toBe(
            EmailChangeRequestStatus::Pending
        )
        ->and(
            EmailChangeRequest::query()
                ->where(
                    'user_id',
                    $user->id,
                )
                ->where(
                    'status',
                    EmailChangeRequestStatus::Pending,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::AUTH_EMAIL_CHANGE_SUPERSEDED,
                )
                ->count()
        )
        ->toBe(1);
});

it('rejects an already occupied email address', function () {
    $user = makeEmailChangeStartUser();

    Person::query()->create([
        'first_name' => 'Andere',
        'last_name' => 'Person',
        'birth_date' => '1985-05-05',
        'email' => 'occupied@example.test',
        'country_code' => 'DE',
    ]);

    expect(
        fn () => app(
            StartEmailChangeAction::class
        )->execute(
            user: $user,
            newEmail: 'occupied@example.test',
        )
    )->toThrow(
        EmailChangeCannotStart::class
    );

    expect(
        EmailChangeRequest::query()->count()
    )->toBe(0);
});

it('rejects the already active email address', function () {
    $user = makeEmailChangeStartUser();

    expect(
        fn () => app(
            StartEmailChangeAction::class
        )->execute(
            user: $user,
            newEmail: 'OLD@EXAMPLE.TEST',
        )
    )->toThrow(
        EmailChangeCannotStart::class
    );
});
