<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Profile\UpdateProfileAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;

function makeProfileActionUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'erika@example.test',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('updates own person data atomically with audit', function () {
    $user = makeProfileActionUser();

    $person = app(
        UpdateProfileAction::class
    )->execute(
        user: $user,
        values: [
            'title' => 'Dr.',
            'first_name' => 'Erika',
            'name_addition' => null,
            'last_name' => 'Beispiel',
            'birth_date' => '1990-01-02',
            'phone' => '0123456789',
            'street' => 'Teststraße',
            'house_number' => '12',
            'postal_code' => '12345',
            'city' => 'Teststadt',
            'country_code' => 'de',
        ],
        ipAddress: '127.0.0.1',
        userAgent: 'Pest Browser',
    );

    expect($person->title)
        ->toBe('Dr.')
        ->and($person->last_name)
        ->toBe('Beispiel')
        ->and($person->country_code)
        ->toBe('DE')
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::PERSON_UPDATED,
                )
                ->count()
        )
        ->toBe(1);
});

it('does not create audit for a profile no op', function () {
    $user = makeProfileActionUser();

    app(UpdateProfileAction::class)->execute(
        user: $user,
        values: [
            'title' => null,
            'first_name' => 'Erika',
            'name_addition' => null,
            'last_name' => 'Muster',
            'birth_date' => '1990-01-02',
            'phone' => null,
            'street' => null,
            'house_number' => null,
            'postal_code' => null,
            'city' => null,
            'country_code' => 'DE',
        ],
    );

    expect(
        AuditEvent::query()
            ->where(
                'event_key',
                AuditEventCatalog::PERSON_UPDATED,
            )
            ->count()
    )->toBe(0);
});

it('never changes email through the general profile action', function () {
    $user = makeProfileActionUser();

    app(UpdateProfileAction::class)->execute(
        user: $user,
        values: [
            'title' => null,
            'first_name' => 'Erika',
            'name_addition' => null,
            'last_name' => 'Muster',
            'birth_date' => '1990-01-02',
            'email' => 'bypass@example.test',
            'phone' => null,
            'street' => null,
            'house_number' => null,
            'postal_code' => null,
            'city' => null,
            'country_code' => 'DE',
        ],
    );

    $user->refresh();
    $user->person->refresh();

    expect($user->email)
        ->toBe('erika@example.test')
        ->and($user->person->email)
        ->toBe('erika@example.test');
});
