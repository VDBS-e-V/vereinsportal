<?php

use App\Modules\Communication\Actions\QueueTemplatedEmailAction;
use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Exceptions\EmailTemplateUnavailable;
use App\Modules\Communication\Jobs\SendTemplatedEmailJob;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplatePlaceholder;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

function createQueueableSystemEmailTemplate(
    bool $active = true,
    int $versionCount = 1,
): EmailTemplate {
    $publisher = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => $active,
        'draft_subject' => 'Entwurf',
        'draft_html' => '<p>Entwurf</p>',
        'updated_by_user_id' => $publisher->id,
    ]);

    foreach ([
        [
            'key' => 'verification_url',
            'label' => 'Verifikationslink',
            'required' => true,
        ],
        [
            'key' => 'expires_at',
            'label' => 'Ablaufzeitpunkt',
            'required' => true,
        ],
        [
            'key' => 'first_name',
            'label' => 'Vorname',
            'required' => false,
        ],
    ] as $placeholder) {
        EmailTemplatePlaceholder::query()->create([
            'email_template_id' => $template->id,
            'key' => $placeholder['key'],
            'label' => $placeholder['label'],
            'description' => $placeholder['label'],
            'example_value' => null,
            'is_required' => $placeholder['required'],
            'is_active' => true,
        ]);
    }

    for ($versionNumber = 1; $versionNumber <= $versionCount; $versionNumber++) {
        EmailTemplateVersion::query()->create([
            'email_template_id' => $template->id,
            'version' => $versionNumber,
            'subject' => "Version {$versionNumber}: Hallo {{ first_name }}",
            'html' => <<<'HTML'
<p>Hallo {{ first_name }}</p>
<a href="{{ verification_url }}">Bestätigen</a>
<p>Gültig bis {{ expires_at }}</p>
HTML,
            'published_by_user_id' => $publisher->id,
            'published_at' => now(),
        ]);
    }

    return $template;
}

it('queues a system email with the latest published template version', function () {
    Queue::fake();

    $template = createQueueableSystemEmailTemplate(
        versionCount: 2,
    );

    $delivery = app(QueueTemplatedEmailAction::class)->execute(
        templateKey: 'auth.registration.verify',
        recipientEmail: 'recipient@example.test',
        values: [
            'first_name' => 'Erika',
            'verification_url' => 'https://example.test/verify',
            'expires_at' => '04.09.2026 12:00',
        ],
    );

    $latestVersion = $template
        ->versions()
        ->where('version', 2)
        ->sole();

    expect($delivery->template_version_id)
        ->toBe($latestVersion->id)
        ->and($delivery->recipient_email)
        ->toBe('recipient@example.test')
        ->and($delivery->subject)
        ->toBe('Version 2: Hallo Erika')
        ->and($delivery->delivery_type)
        ->toBe(EmailDeliveryType::System)
        ->and($delivery->status)
        ->toBe(EmailDeliveryStatus::Queued)
        ->and($delivery->attempts)
        ->toBe(0)
        ->and($delivery->queued_at)
        ->not->toBeNull();

    Queue::assertPushed(
        SendTemplatedEmailJob::class,
        function (SendTemplatedEmailJob $job) use (
            $delivery,
        ): bool {
            return $job->emailDeliveryId === $delivery->id
                && $job->afterCommit === true;
        }
    );
});

it('does not queue an inactive template', function () {
    Queue::fake();

    createQueueableSystemEmailTemplate(
        active: false,
    );

    expect(
        fn () => app(QueueTemplatedEmailAction::class)->execute(
            templateKey: 'auth.registration.verify',
            recipientEmail: 'recipient@example.test',
            values: [
                'first_name' => 'Erika',
                'verification_url' => 'https://example.test/verify',
                'expires_at' => '04.09.2026 12:00',
            ],
        )
    )->toThrow(EmailTemplateUnavailable::class);

    expect(EmailDelivery::query()->count())
        ->toBe(0);

    Queue::assertNothingPushed();
});

it('does not queue a template without a published version', function () {
    Queue::fake();

    createQueueableSystemEmailTemplate(
        versionCount: 0,
    );

    expect(
        fn () => app(QueueTemplatedEmailAction::class)->execute(
            templateKey: 'auth.registration.verify',
            recipientEmail: 'recipient@example.test',
            values: [],
        )
    )->toThrow(EmailTemplateUnavailable::class);

    expect(EmailDelivery::query()->count())
        ->toBe(0);

    Queue::assertNothingPushed();
});

it('does not run the queued mail when an outer transaction rolls back', function () {
    Mail::fake();

    createQueueableSystemEmailTemplate();

    $startingTransactionLevel = DB::transactionLevel();

    DB::beginTransaction();

    try {
        app(QueueTemplatedEmailAction::class)->execute(
            templateKey: 'auth.registration.verify',
            recipientEmail: 'recipient@example.test',
            values: [
                'first_name' => 'Erika',
                'verification_url' => 'https://example.test/verify',
                'expires_at' => '04.09.2026 12:00',
            ],
        );

        /*
         * Innerhalb der offenen Transaktion existiert die
         * Versandhistorie bereits.
         */
        expect(EmailDelivery::query()->count())
            ->toBe(1);

        /*
         * Der Sync-Queue-Job darf wegen afterCommit
         * noch nicht ausgeführt worden sein.
         */
        Mail::assertNothingSent();
    } finally {
        if (
            DB::transactionLevel()
            > $startingTransactionLevel
        ) {
            DB::rollBack();
        }
    }

    /*
     * Rollback entfernt auch die EmailDelivery.
     */
    expect(EmailDelivery::query()->count())
        ->toBe(0);

    /*
     * Und der afterCommit-Job wurde nie ausgeführt.
     */
    Mail::assertNothingSent();
});