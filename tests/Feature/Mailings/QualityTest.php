<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Models\MailSoftBounce;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\MailTemplateVersion;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Services\SenderCheck;
use App\Modules\People\Models\Person;
use App\Modules\System\Services\SystemChecks;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function qaUser(RoleKey $role): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function qaHook($test, array $payload, ?string $signature = null, string $secret = 'geheim'): Illuminate\Testing\TestResponse
{
    $body = json_encode($payload);

    return $test->call('POST', '/webhooks/mail', [], [], [], ['HTTP_X_SIGNATURE' => $signature ?? hash_hmac('sha256', $body, $secret), 'CONTENT_TYPE' => 'application/json'], $body);
}

it('checks SPF, DMARC and DKIM of the sender domain with simulated answers', function () {
    config(['mail.from.address' => 'portal@verein.example', 'portal.mailings.dkim_selector' => 'sel1']);
    $dns = fn (array $map): SenderCheck => new SenderCheck(fn (string $host): array => $map[$host] ?? []);
    $byLabel = fn (array $checks): array => collect($checks)->pluck('status', 'label')->all();

    $missing = $byLabel($dns([])->run());
    expect($missing['SPF'])->toBe('error')->and($missing['DMARC'])->toBe('warning')->and($missing['DKIM'])->toBe('error');

    $good = $byLabel($dns(['verein.example' => ['v=spf1 include:mail.example -all'], '_dmarc.verein.example' => ['v=DMARC1; p=none'], 'sel1._domainkey.verein.example' => ['v=DKIM1; k=rsa; p=abc']])->run());
    expect($good)->toMatchArray(['SPF' => 'ok', 'DMARC' => 'ok', 'DKIM' => 'ok']);

    $open = $byLabel($dns(['verein.example' => ['v=spf1 +all']])->run());
    expect($open['SPF'])->toBe('warning');

    config(['mail.from.address' => 'kaputt']);
    expect($dns([])->run()[0]['status'])->toBe('error');
});

it('rejects the webhook without secret, with a wrong signature and accepts the right one', function () {
    $event = ['type' => 'hard_bounce', 'email' => 'weg@beispiel.example'];

    qaHook($this, $event)->assertNotFound();

    config(['portal.mailings.webhook_secret' => 'geheim']);
    qaHook($this, $event, 'falsch')->assertUnauthorized();
    expect(MailSuppression::query()->count())->toBe(0);

    qaHook($this, $event)->assertOk()->assertJson(['blocked' => 1]);
    expect(app(MailSuppressions::class)->isSuppressed('weg@beispiel.example'))->toBeTrue();
});

it('blocks complaints at once and soft bounces only at the limit', function () {
    config(['portal.mailings.webhook_secret' => 'geheim', 'portal.mailings.soft_bounce_limit' => 3]);

    qaHook($this, ['events' => [['type' => 'complaint', 'email' => 'klage@beispiel.example'], ['type' => 'hard_bounce', 'email' => 'kein-email'], ['type' => 'unbekannt', 'email' => 'x@beispiel.example']]])->assertOk();
    expect(app(MailSuppressions::class)->isSuppressed('klage@beispiel.example'))->toBeTrue()->and(MailSuppression::query()->count())->toBe(1);

    foreach ([1, 2] as $i) {
        qaHook($this, ['type' => 'soft_bounce', 'email' => 'voll@beispiel.example'])->assertOk();
    }

    expect(app(MailSuppressions::class)->isSuppressed('voll@beispiel.example'))->toBeFalse()->and(MailSoftBounce::query()->firstOrFail()->count)->toBe(2);

    qaHook($this, ['type' => 'soft_bounce', 'email' => 'voll@beispiel.example'])->assertOk()->assertJson(['blocked' => 1]);
    expect(app(MailSuppressions::class)->isSuppressed('voll@beispiel.example'))->toBeTrue()->and(MailSoftBounce::query()->count())->toBe(0);
});

it('shows the quality page to those who manage templates and runs the sender check on request', function () {
    config(['mail.from.address' => 'portal@verein.example']);
    app()->instance(SenderCheck::class, new SenderCheck(fn (string $host): array => $host === 'verein.example' ? ['v=spf1 -all'] : []));
    Mailing::query()->create(['kind' => 'general', 'subject' => 'A', 'body' => '<p>a</p>', 'status' => 'done', 'recipients_count' => 10, 'sent_count' => 8, 'failed_count' => 2, 'sent_by_user_id' => qaUser(RoleKey::AdministrationStaff)->id]);

    $this->actingAs(qaUser(RoleKey::Member))->get(route('mailings.quality.index'))->assertForbidden();

    $this->actingAs(qaUser(RoleKey::AdministrationStaff));
    $this->get(route('mailings.quality.index'))->assertOk()->assertSee('Zustellqualität')->assertSee('20,0 %')->assertSee('Hoch');
    $this->post(route('mailings.quality.check'))->assertRedirect(route('mailings.quality.index'));
    $this->get(route('mailings.quality.index'))->assertSee('SPF')->assertSee('Vorhanden und streng');
});

it('warns about a high failure rate on the progress page and in the system checks', function () {
    $staff = qaUser(RoleKey::AdministrationStaff);
    $bad = Mailing::query()->create(['kind' => 'general', 'subject' => 'A', 'body' => '<p>a</p>', 'status' => 'done', 'recipients_count' => 10, 'sent_count' => 8, 'failed_count' => 2, 'sent_by_user_id' => $staff->id, 'sender_area' => 'office', 'groups' => ['team']]);
    $fine = Mailing::query()->create(['kind' => 'general', 'subject' => 'B', 'body' => '<p>b</p>', 'status' => 'done', 'recipients_count' => 100, 'sent_count' => 99, 'failed_count' => 1, 'sent_by_user_id' => $staff->id, 'sender_area' => 'office', 'groups' => ['team']]);
    $this->actingAs($staff);

    $this->get(route('mailings.office.show', $bad))->assertOk()->assertSee('Hohe Fehlerquote');
    $this->get(route('mailings.office.show', $fine))->assertOk()->assertDontSee('Hohe Fehlerquote');
    expect(json_encode(app(SystemChecks::class)->all()))->toContain('Zustellqualität');
});

it('creates a follow-up draft for the failed addresses and sends it to exactly those', function () {
    Queue::fake();
    $staff = qaUser(RoleKey::AdministrationStaff);
    $mailing = Mailing::query()->create(['kind' => 'general', 'subject' => 'Einladung', 'body' => '<p>Hallo {name}</p>', 'status' => 'done', 'recipients_count' => 3, 'sent_count' => 1, 'failed_count' => 2, 'sent_by_user_id' => $staff->id, 'sender_area' => 'office', 'groups' => ['team']]);

    foreach (['ok@beispiel.example' => 'sent', 'a@beispiel.example' => 'failed', 'B@beispiel.example' => 'failed'] as $email => $status) {
        MailingDelivery::query()->create(['mailing_id' => $mailing->id, 'email' => $email, 'first_name' => '', 'last_name' => '', 'fallback' => 'Mitglied', 'group' => 'team', 'status' => $status]);
    }

    $this->actingAs($staff)->get(route('mailings.office.show', $mailing))->assertOk()->assertSee('Nachfassen an nicht Erreichte');
    $this->post(route('mailings.office.followup', $mailing))->assertRedirect();

    $draft = Mailing::query()->where('status', Mailing::STATUS_DRAFT)->firstOrFail();
    expect($draft->chosenPeople())->toBe(['e:a@beispiel.example', 'e:b@beispiel.example'])->and($draft->groups)->toBe([]);
    $this->get(route('mailings.office.create', ['entwurf' => $draft->id]))->assertOk()->assertSee('a@beispiel.example');

    $sent = app(Mailings::class)->submit($draft, $staff);
    expect($sent->status)->toBe('queued')->and(MailingDelivery::query()->where('mailing_id', $draft->id)->pluck('email')->sort()->values()->all())->toBe(['a@beispiel.example', 'b@beispiel.example']);

    $this->actingAs(qaUser(RoleKey::AdministrationStaff))->post(route('mailings.office.followup', $mailing))->assertForbidden();
});

it('keeps earlier versions of a template and restores them', function () {
    $staff = qaUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);
    $template = app(MailTemplates::class)->createGeneral('Einladung', null, 'Betreff eins', '<p>Text eins</p>', $staff);

    $this->put(route('mailings.templates.update', $template), ['name' => 'Einladung', 'subject' => 'Betreff zwei', 'body' => '<p>Text zwei</p>'])->assertRedirect();
    $this->put(route('mailings.templates.update', $template), ['name' => 'Einladung', 'subject' => 'Betreff zwei', 'body' => '<p>Text zwei</p>'])->assertRedirect();
    expect(MailTemplateVersion::query()->count())->toBe(1);

    $this->get(route('mailings.templates.edit', $template))->assertOk()->assertSee('Frühere Stände')->assertSee('Betreff eins');

    $version = MailTemplateVersion::query()->firstOrFail();
    $this->post(route('mailings.templates.restore', [$template, $version]))->assertRedirect();

    expect($template->fresh()->subject)->toBe('Betreff eins')->and(MailTemplateVersion::query()->count())->toBe(2);

    $other = MailTemplate::query()->create(['key' => 'x', 'kind' => 'general', 'name' => 'Andere', 'subject' => 'S', 'body' => '<p>b</p>']);
    $this->post(route('mailings.templates.restore', [$other, $version]))->assertNotFound();
});
