<?php

use App\Modules\Audit\Enums\AuditActorType;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Communication\Actions\DeactivateEmailTemplateAction;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

function createDeactivatableEmailTemplate(): array
{
    $actor = User::query()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $template = EmailTemplate::query()->create([
        'key' => 'auth.registration.verify',
        'name' => 'Registrierung bestätigen',
        'is_active' => true,
        'draft_subject' => 'Registrierung bestätigen',
        'draft_html' => '<p>Registrierung bestätigen</p>',
        'updated_by_user_id' => $actor->id,
    ]);

    return [
        $template,
        $actor,
    ];
}

it('deactivates an email template with audit', function () {
    [$template, $actor] = createDeactivatableEmailTemplate();

    $deactivated = app(
        DeactivateEmailTemplateAction::class
    )->execute(
        templateId: $template->id,
        actor: $actor,
        ipAddress: '192.0.2.10',
        userAgent: 'Pest Browser',
        deviceInfo: [
            'client' => 'test',
        ],
    );

    expect($deactivated->is_active)
        ->toBeFalse()
        ->and(
            $template->fresh()->is_active
        )
        ->toBeFalse();

    $audit = AuditEvent::query()
        ->where(
            'event_key',
            AuditEventCatalog::EMAIL_TEMPLATE_DEACTIVATED,
        )
        ->sole();

    expect($audit->actor_type)
        ->toBe(AuditActorType::User)
        ->and($audit->actor_user_id)
        ->toBe($actor->id)
        ->and($audit->subject_type)
        ->toBe('email_template')
        ->and($audit->subject_id)
        ->toBe($template->id)
        ->and($audit->old_values['status'])
        ->toBe('active')
        ->and($audit->new_values['status'])
        ->toBe('inactive')
        ->and($audit->ip_address)
        ->toBe('192.0.2.10')
        ->and($audit->user_agent)
        ->toBe('Pest Browser')
        ->and($audit->device_info)
        ->toBe([
            'client' => 'test',
        ]);
});

it('does not create another audit event when already inactive', function () {
    [$template, $actor] = createDeactivatableEmailTemplate();

    $action = app(
        DeactivateEmailTemplateAction::class
    );

    $action->execute(
        templateId: $template->id,
        actor: $actor,
    );

    $action->execute(
        templateId: $template->id,
        actor: $actor,
    );

    expect($template->fresh()->is_active)
        ->toBeFalse()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_DEACTIVATED,
                )
                ->count()
        )
        ->toBe(1);
});

it('rolls deactivation and audit back with an outer transaction', function () {
    [$template, $actor] = createDeactivatableEmailTemplate();

    $startingTransactionLevel = DB::transactionLevel();

    DB::beginTransaction();

    try {
        app(
            DeactivateEmailTemplateAction::class
        )->execute(
            templateId: $template->id,
            actor: $actor,
        );

        expect($template->fresh()->is_active)
            ->toBeFalse()
            ->and(
                AuditEvent::query()
                    ->where(
                        'event_key',
                        AuditEventCatalog::EMAIL_TEMPLATE_DEACTIVATED,
                    )
                    ->count()
            )
            ->toBe(1);
    } finally {
        if (
            DB::transactionLevel()
            > $startingTransactionLevel
        ) {
            DB::rollBack();
        }
    }

    expect($template->fresh()->is_active)
        ->toBeTrue()
        ->and(
            AuditEvent::query()
                ->where(
                    'event_key',
                    AuditEventCatalog::EMAIL_TEMPLATE_DEACTIVATED,
                )
                ->count()
        )
        ->toBe(0);
});
