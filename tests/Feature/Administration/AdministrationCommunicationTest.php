<?php

use App\Modules\Communication\Enums\EmailDeliveryStatus;
use App\Modules\Communication\Enums\EmailDeliveryType;
use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Identity\Enums\RoleAssignmentSource;
use App\Modules\Identity\Enums\RoleKey;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\RoleAssignment;
use App\Modules\Identity\Models\User;
use Database\Seeders\PortalInvitationEmailTemplateSeeder;

function communicationAdministrationActor(
    RoleKey $roleKey,
    string $email,
): User {
    $actor = User::query()->create([
        'email' => $email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $actor->email_verified_at = now();
    $actor->save();

    $role = Role::query()->firstOrCreate(
        ['key' => $roleKey->value],
        [
            'name' => match ($roleKey) {
                RoleKey::Administration => 'Administration',
                RoleKey::AdministrationStaff => 'Verwaltung',
                default => $roleKey->value,
            },
            'is_system' => true,
        ],
    );

    RoleAssignment::query()->create([
        'user_id' => $actor->id,
        'role_id' => $role->id,
        'source' => RoleAssignmentSource::Console,
        'starts_at' => now()->subMinute(),
    ]);

    return $actor->refresh();
}

function communicationAdministrationSession(): array
{
    return [
        'identity.session_version' => 1,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('allows administration staff to inspect communication data but not edit templates', function () {
    $this->seed(PortalInvitationEmailTemplateSeeder::class);
    $staff = communicationAdministrationActor(
        RoleKey::AdministrationStaff,
        'communication-staff@example.test',
    );
    $template = EmailTemplate::query()->where('key', 'auth.portal-invitation')->firstOrFail();

    $this->withSession(communicationAdministrationSession())
        ->actingAs($staff)
        ->get(route('administration.communication.templates.index'))
        ->assertOk()
        ->assertSee('Portalzugang einladen');

    $this->withSession(communicationAdministrationSession())
        ->actingAs($staff)
        ->get(route('administration.communication.templates.show', $template))
        ->assertOk()
        ->assertSee('auth.portal-invitation');

    $this->withSession(communicationAdministrationSession())
        ->actingAs($staff)
        ->put(route('administration.communication.templates.draft.update', $template), [
            'draft_subject' => 'Nicht erlaubt',
            'draft_html' => '<p>Nicht erlaubt</p>',
        ])
        ->assertForbidden();

    expect($template->refresh()->draft_subject)->toBe('Einladung zum Vereinsportal');
});

it('allows administration to edit publish and activate a template', function () {
    $this->seed(PortalInvitationEmailTemplateSeeder::class);
    $admin = communicationAdministrationActor(
        RoleKey::Administration,
        'communication-admin@example.test',
    );
    $template = EmailTemplate::query()->where('key', 'auth.portal-invitation')->firstOrFail();

    $this->withSession(communicationAdministrationSession())
        ->actingAs($admin)
        ->put(route('administration.communication.templates.draft.update', $template), [
            'draft_subject' => 'Ihr Portalzugang',
            'draft_html' => '<p>Hallo {{ first_name }}</p><p><a href="{{ invitation_url }}">Zugang einrichten</a></p><p>Gültig bis {{ expires_at }}</p>',
        ])
        ->assertRedirect(route('administration.communication.templates.show', $template));

    $this->withSession(communicationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.communication.templates.publish', $template))
        ->assertRedirect(route('administration.communication.templates.show', $template));

    expect($template->versions()->count())->toBe(1)
        ->and($template->versions()->firstOrFail()->subject)->toBe('Ihr Portalzugang');

    $this->withSession(communicationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.communication.templates.status.update', $template), [
            'active' => true,
        ])
        ->assertRedirect(route('administration.communication.templates.show', $template));

    expect($template->refresh()->is_active)->toBeTrue();
});

it('shows and filters delivery metadata without requiring stored message bodies', function () {
    $this->seed(PortalInvitationEmailTemplateSeeder::class);
    $admin = communicationAdministrationActor(
        RoleKey::Administration,
        'communication-delivery-admin@example.test',
    );
    $staff = communicationAdministrationActor(
        RoleKey::AdministrationStaff,
        'communication-delivery-staff@example.test',
    );
    $template = EmailTemplate::query()->where('key', 'auth.portal-invitation')->firstOrFail();

    $this->withSession(communicationAdministrationSession())
        ->actingAs($admin)
        ->post(route('administration.communication.templates.publish', $template));

    $version = $template->versions()->firstOrFail();
    $delivery = EmailDelivery::query()->create([
        'template_version_id' => $version->id,
        'sender_user_id' => null,
        'recipient_email' => 'delivery-target@example.test',
        'subject' => 'Einladung zum Vereinsportal',
        'delivery_type' => EmailDeliveryType::System,
        'status' => EmailDeliveryStatus::Sent,
        'attempts' => 1,
        'queued_at' => now()->subMinute(),
        'sent_at' => now(),
        'failed_at' => null,
        'last_error_class' => null,
    ]);

    $this->withSession(communicationAdministrationSession())
        ->actingAs($staff)
        ->get(route('administration.communication.deliveries.index', [
            'status' => EmailDeliveryStatus::Sent->value,
            'template' => 'auth.portal-invitation',
            'q' => 'delivery-target',
        ]))
        ->assertOk()
        ->assertSee('delivery-target@example.test')
        ->assertSee('auth.portal-invitation')
        ->assertSee('Gesendet');

    $this->withSession(communicationAdministrationSession())
        ->actingAs($staff)
        ->get(route('administration.communication.deliveries.show', $delivery))
        ->assertOk()
        ->assertSee('delivery-target@example.test')
        ->assertSee('Einladung zum Vereinsportal')
        ->assertSee('gerenderte Nachrichtentext')
        ->assertSee('auth.portal-invitation');
});
