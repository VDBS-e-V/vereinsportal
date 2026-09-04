<?php

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\QueueRegistrationVerificationEmailAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\RegistrationRequest;
use App\Modules\Identity\Models\User;
use Database\Seeders\RegistrationVerificationEmailTemplateSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('queues the registration verification email from the registration request', function () {
    Queue::fake();

    /*
     * Den verbindlichen Placeholder-Katalog verwenden.
     */
    $this->seed(
        RegistrationVerificationEmailTemplateSeeder::class
    );

    $template = EmailTemplate::query()
        ->where('key', 'auth.registration.verify')
        ->sole();

    $publisher = User::query()->create([
        'email' => 'publisher@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    /*
     * Für diesen isolierten Test stellen wir eine
     * veröffentlichte aktive Version bereit.
     */
    $template->update([
        'is_active' => true,
    ]);

    $version = EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,

        /*
         * Damit prüfen wir auch, dass first_name und
         * privacy_notice_version an Communication
         * weitergegeben werden.
         */
        'subject' => 'Hallo {{ first_name }} – Datenschutz {{ privacy_notice_version }}',

        /*
         * Beide Pflichtplaceholder müssen beim Rendern
         * vorhanden sein, sonst würde QueueTemplatedEmailAction
         * bereits hier abbrechen.
         */
        'html' => <<<'HTML'
<p>Bitte bestätigen Sie Ihre Registrierung.</p>
<a href="{{ verification_url }}">Registrierung bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
HTML,

        'published_by_user_id' => $publisher->id,
        'published_at' => now(),
    ]);

    $registrationRequest = RegistrationRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-05-10',

        /*
         * Absichtlich verschieden vom Verifikationsempfänger.
         * So testen wir, welches Feld tatsächlich verwendet wird.
         */
        'email' => 'registration@example.test',

        'password' => Hash::make('Sicher123!'),
        'privacy_notice_version' => 'privacy-v1',
        'consented_at' => now(),

        'verification_recipient_email' => 'verification@example.test',
        'verification_version' => 1,
        'verification_expires_at' => now()->addDays(3),
        'expires_at' => now()->addDays(7),
        'status' => 'pending_verification',
    ]);

    $delivery = app(
        QueueRegistrationVerificationEmailAction::class
    )->execute(
        registrationRequest: $registrationRequest,
    );

    expect($delivery->template_version_id)
        ->toBe($version->id)
        ->and($delivery->recipient_email)
        ->toBe('verification@example.test')
        ->and($delivery->subject)
        ->toBe(
            'Hallo Erika – Datenschutz privacy-v1'
        )
        ->and($delivery->delivery_type)
        ->toBe(EmailDeliveryType::System)
        ->and($delivery->status)
        ->toBe(EmailDeliveryStatus::Queued)
        ->and($delivery->attempts)
        ->toBe(0);

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (
            SendTemplatedEmailJob $job
        ) use ($delivery): bool {
            return $job->emailDeliveryId === $delivery->id
                && $job->afterCommit === true;
        }
    );
});
