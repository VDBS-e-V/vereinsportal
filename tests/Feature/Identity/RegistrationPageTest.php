<?php

use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

it('shows the public registration page on the my domain', function () {
    $response = $this->get(
        'http://my.vdb.test/registrieren'
    );

    $response
        ->assertOk()
        ->assertSee('Registrieren')
        ->assertSee('Vorname')
        ->assertSee('Nachname')
        ->assertSee('Geburtsdatum')
        ->assertSee('E-Mail-Adresse')
        ->assertSee('Passwort')
        ->assertSee(
            'Ich stimme der Verarbeitung meiner Daten'
        );
});

it('does not expose the registration page on another module domain', function () {
    $this
        ->get(
            'http://verwaltung.vdb.test/registrieren'
        )
        ->assertNotFound();
});

function prepareRegistrationPageVerificationTemplate(): void
{
    test()->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.registration.verify')
        ->sole();

    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Registrierung bestätigen',
        'html' => <<<'HTML'
<p>Hallo {{ first_name }}</p>
<p>
    <a href="{{ verification_url }}">
        Registrierung bestätigen
    </a>
</p>
<p>Gültig bis {{ expires_at }}</p>
HTML,
        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);
}

it('submits the public registration form', function () {
    Queue::fake();

    config()->set(
        'privacy.registration_notice_version',
        '2026-09-01T21:04:00Z',
    );

    prepareRegistrationPageVerificationTemplate();

    $component = Volt::test('identity.registration')
        ->set('first_name', 'Erika')
        ->set('last_name', 'Mustermann')
        ->set('birth_date', '1990-05-10')
        ->set('email', 'Erika@example.test')
        ->set('password', 'Sicher123!')
        ->set('privacy_accepted', true)
        ->call('register')
        ->assertHasNoErrors();

    $registrationRequest = RegistrationRequest::query()
        ->sole();

    $component->assertRedirect(
        route(
            'my.registration.status',
            [
                'publicId' => $registrationRequest->public_id,
            ],
        )
    );

    expect($registrationRequest->email)
        ->toBe('erika@example.test')
        ->and(
            $registrationRequest->privacy_notice_version
        )
        ->toBe('2026-09-01T21:04:00Z')
        ->and(
            $registrationRequest->consented_at
        )
        ->not->toBeNull();

    expect(
        EmailDelivery::query()->count()
    )->toBe(1);

    Queue::assertPushed(
        SendTemplatedEmailJob::class
    );
});

it('shows a neutral registration error for a possible duplicate', function () {
    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-05-10',
        'email' => 'erika@example.test',
    ]);

    Volt::test('identity.registration')
        ->set('first_name', 'Erika')
        ->set('last_name', 'Mustermann')
        ->set('birth_date', '1990-05-10')
        ->set('email', 'erika@example.test')
        ->set('password', 'Sicher123!')
        ->set('privacy_accepted', true)
        ->call('register')
        ->assertHasErrors([
            'registration',
        ]);

    expect(
        RegistrationRequest::query()->count()
    )->toBe(0);
});

it('keeps the browser flow controlled when the verification template is unavailable', function () {
    config()->set(
        'privacy.registration_notice_version',
        '2026-09-01T21:04:00Z',
    );

    $component = Volt::test('identity.registration')
        ->set('first_name', 'Erika')
        ->set('last_name', 'Mustermann')
        ->set('birth_date', '1990-05-10')
        ->set('email', 'erika@example.test')
        ->set('password', 'Sicher123!')
        ->set('privacy_accepted', true)
        ->call('register')
        ->assertHasNoErrors();

    $registrationRequest = RegistrationRequest::query()
        ->sole();

    $component->assertRedirect(
        route(
            'my.registration.status',
            [
                'publicId' => $registrationRequest->public_id,
            ],
        )
    );

    expect(
        $registrationRequest->verification_sent_at
    )
        ->toBeNull()
        ->and(
            EmailDelivery::query()->count()
        )
        ->toBe(0);
});
