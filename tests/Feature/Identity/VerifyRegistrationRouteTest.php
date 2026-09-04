<?php

use App\Modules\Identity\Actions\Registration\StartRegistrationAction;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\RegistrationVerificationUrl;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function registrationForRouteVerification(): RegistrationRequest
{
    return app(StartRegistrationAction::class)->execute(
        firstName: 'Erika',
        lastName: 'Mustermann',
        birthDate: '1990-01-15',
        email: 'erika@example.test',
        password: 'Sicher123!',
        privacyAccepted: true,
        privacyNoticeVersion: 'privacy-v1',
        consentedAt: now(),
    );
}

it('completes registration through the signed verification route', function () {
    $registrationRequest = registrationForRouteVerification();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    $this
        ->withHeader('User-Agent', 'Pest Browser')
        ->get($url)
        ->assertOk()
        ->assertSeeText(
            'Die Registrierung wurde erfolgreich abgeschlossen.'
        );

    expect(RegistrationRequest::query()->count())
        ->toBe(0)
        ->and(Person::query()->count())
        ->toBe(1)
        ->and(User::query()->count())
        ->toBe(1);
});

it('cannot reuse a verification link after successful registration', function () {
    $registrationRequest = registrationForRouteVerification();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    $this
        ->get($url)
        ->assertOk();

    $secondResponse = $this->get($url);

    expect($secondResponse->status())
        ->not->toBe(200)
        ->and(RegistrationRequest::query()->count())
        ->toBe(0)
        ->and(
            Person::query()
                ->where('email', 'erika@example.test')
                ->count()
        )
        ->toBe(1)
        ->and(
            User::query()
                ->where('email', 'erika@example.test')
                ->count()
        )
        ->toBe(1);
});

it('returns a controlled response when completion is no longer possible', function () {
    $registrationRequest = registrationForRouteVerification();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'different@example.test',
        'country_code' => 'DE',
    ]);

    $this->get($url)
        ->assertUnprocessable()
        ->assertSeeText(
            'Zu den eingegebenen Daten existiert möglicherweise bereits ein Datensatz.'
        );

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(User::query()->count())
        ->toBe(0);
});

it('still rejects a manipulated signed verification route', function () {
    $registrationRequest = registrationForRouteVerification();

    $url = app(RegistrationVerificationUrl::class)
        ->create($registrationRequest);

    $tamperedUrl = str_replace(
        '/1?',
        '/2?',
        $url,
    );

    $this->get($tamperedUrl)
        ->assertForbidden();

    expect(RegistrationRequest::query()->count())
        ->toBe(1)
        ->and(User::query()->count())
        ->toBe(0);
});
