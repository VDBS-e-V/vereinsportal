<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateVersion;
use App\Modules\Identity\Actions\PortalInvitation\StartPortalInvitationWorkflowAction;
use App\Modules\Identity\Enums\PortalInvitationStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Database\Seeders\PortalInvitationEmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;

it('prepares the portal invitation delivery when the template is available', function () {
    Queue::fake();

    $this->seed(PortalInvitationEmailTemplateSeeder::class);

    $template = EmailTemplate::query()
        ->where('key', 'auth.portal-invitation')
        ->sole();

    $actor = User::query()->create([
        'email' => 'portal-invitation-workflow-admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $actor->email_verified_at = now();
    $actor->save();

    EmailTemplateVersion::query()->create([
        'email_template_id' => $template->id,
        'version' => 1,
        'subject' => 'Einladung zum Vereinsportal',
        'html' => <<<'HTML'
<p>Hallo {{ first_name }},</p>
<p><a href="{{ invitation_url }}">Portalzugang einrichten</a></p>
<p>Der Link ist bis {{ expires_at }} gültig.</p>
HTML,
        'published_by_user_id' => $actor->id,
        'published_at' => now(),
    ]);

    $template->update([
        'is_active' => true,
    ]);

    $person = Person::query()->create([
        'first_name' => 'Lena',
        'last_name' => 'Beispiel',
        'birth_date' => '1990-02-03',
        'email' => 'portal-invitation-workflow@example.test',
        'country_code' => 'DE',
    ]);

    $invitation = app(StartPortalInvitationWorkflowAction::class)
        ->execute($person, $actor);

    $delivery = EmailDelivery::query()
        ->where('recipient_email', $person->email)
        ->sole();

    expect($invitation->status())
        ->toBe(PortalInvitationStatus::Sent)
        ->and($invitation->sent_at)
        ->not->toBeNull()
        ->and($delivery->templateVersion->email_template_id)
        ->toBe($template->id)
        ->and($delivery->subject)
        ->toBe('Einladung zum Vereinsportal');
});
