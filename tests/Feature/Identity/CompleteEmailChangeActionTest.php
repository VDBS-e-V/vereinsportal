<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\EmailChange\CompleteEmailChangeAction;
use App\Modules\Identity\Actions\EmailChange\StartEmailChangeAction;
use App\Modules\Identity\Enums\EmailChangeRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Exceptions\EmailChangeCannotComplete;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

function makeEmailChangeCompleteUser(): User
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
        'session_version' => 4,
    ]);

    $user->email_verified_at = now();
    $user->remember_token = 'existing-token';
    $user->save();

    return $user->refresh();
}

it('completes an email change atomically and invalidates sessions', function () {
    $user = makeEmailChangeCompleteUser();

    $request = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    DB::table('sessions')->insert([
        [
            'id' => 'session-one',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser One',
            'payload' => 'one',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'session-two',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Browser Two',
            'payload' => 'two',
            'last_activity' => now()->timestamp,
        ],
    ]);

    app(
        CompleteEmailChangeAction::class
    )->execute(
        publicId: $request->public_id,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    $user->refresh();
    $user->person->refresh();
    $request->refresh();

    expect($user->email)
        ->toBe('new@example.test')
        ->and($user->person->email)
        ->toBe('new@example.test')
        ->and($user->session_version)
        ->toBe(5)
        ->and($user->remember_token)
        ->toBeNull()
        ->and($request->status)
        ->toBe(
            EmailChangeRequestStatus::Confirmed
        )
        ->and($request->confirmed_at)
        ->not->toBeNull()
        ->and(
            DB::table('sessions')
                ->where(
                    'user_id',
                    $user->id,
                )
                ->count()
        )
        ->toBe(0)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::
                        AUTH_EMAIL_CHANGE_COMPLETED,
                )
                ->count()
        )
        ->toBe(1)
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::
                        AUTH_SESSIONS_INVALIDATED,
                )
                ->count()
        )
        ->toBe(1);
});

it('rejects a superseded email change link', function () {
    $user = makeEmailChangeCompleteUser();

    $start = app(
        StartEmailChangeAction::class
    );

    $old = $start->execute(
        user: $user,
        newEmail: 'first@example.test',
    );

    $start->execute(
        user: $user,
        newEmail: 'second@example.test',
    );

    expect(
        fn () => app(
            CompleteEmailChangeAction::class
        )->execute(
            publicId: $old->public_id,
        )
    )->toThrow(
        EmailChangeCannotComplete::class
    );

    $user->refresh();

    expect($user->email)
        ->toBe('old@example.test');
});

it('rejects an expired email change', function () {
    $user = makeEmailChangeCompleteUser();

    $request = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'new@example.test',
    );

    $request->expires_at =
        now()->subSecond();

    $request->save();

    expect(
        fn () => app(
            CompleteEmailChangeAction::class
        )->execute(
            publicId: $request->public_id,
        )
    )->toThrow(
        EmailChangeCannotComplete::class
    );
});

it('rolls back completely when the target email became occupied', function () {
    $user = makeEmailChangeCompleteUser();

    $request = app(
        StartEmailChangeAction::class
    )->execute(
        user: $user,
        newEmail: 'race@example.test',
    );

    Person::query()->create([
        'first_name' => 'Parallel',
        'last_name' => 'Person',
        'birth_date' => '1980-01-01',
        'email' => 'race@example.test',
        'country_code' => 'DE',
    ]);

    expect(
        fn () => app(
            CompleteEmailChangeAction::class
        )->execute(
            publicId: $request->public_id,
        )
    )->toThrow(
        EmailChangeCannotComplete::class
    );

    $user->refresh();
    $user->person->refresh();
    $request->refresh();

    expect($user->email)
        ->toBe('old@example.test')
        ->and($user->person->email)
        ->toBe('old@example.test')
        ->and($user->session_version)
        ->toBe(4)
        ->and($user->remember_token)
        ->toBe('existing-token')
        ->and($request->status)
        ->toBe(
            EmailChangeRequestStatus::Pending
        );
});