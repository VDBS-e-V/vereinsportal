<?php

use App\Modules\Access\Mail\AccountDeletedMail;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessRequests\Mail\AccessRequestReceivedMail;
use App\Modules\MailBuilder\Mail\BuilderTestMail;
use App\Modules\MailBuilder\Models\MailAsset;
use App\Modules\MailBuilder\Models\MailLayout;
use App\Modules\MailBuilder\Models\MailTemplate;
use App\Modules\MailBuilder\Support\LayoutSettings;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
});

function mbpUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** @return array<string, mixed> */
function mbpDoc(string $html): array
{
    return ['version' => 1, 'preheader' => '', 'blocks' => [['id' => 'a1', 'type' => 'text', 'props' => ['html' => $html]]]];
}

it('shows the builder only to people who manage system mails', function () {
    $this->get(route('mailbuilder.system.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Member, RoleKey::Coordination, RoleKey::BoardMember] as $role) {
        $this->actingAs(mbpUser($role))->get(route('mailbuilder.system.index'))->assertForbidden();
    }

    $this->actingAs(mbpUser(RoleKey::AdministrationStaff))->get(route('mailbuilder.system.index'))->assertOk()->assertSee('Passwort zurücksetzen')->assertSee('Antrag auf Zugang eingegangen');
});

it('lets the office edit ordinary mails and the administration also the security mails', function () {
    $office = mbpUser(RoleKey::AdministrationStaff);

    $this->actingAs($office)->get(route('mailbuilder.system.edit', 'access_requests.received'))->assertOk()->assertSee('data-mail-builder', false)->assertDontSee('Nur lesen');
    $this->get(route('mailbuilder.system.edit', 'access.password_reset'))->assertOk()->assertSee('Nur lesen');
    $this->put(route('mailbuilder.system.update', 'access.password_reset'), ['doc' => json_encode(mbpDoc('<p>{url}</p>')), 'subject' => 'Neu'])->assertSessionHasErrors('doc');
    $this->post(route('mailbuilder.system.reset', 'access.password_reset'))->assertForbidden();

    $this->actingAs(mbpUser(RoleKey::Administration))->get(route('mailbuilder.system.edit', 'access.password_reset'))->assertOk()->assertDontSee('Nur lesen');
    $this->get(route('mailbuilder.system.edit', 'gibt.es_nicht'))->assertNotFound();
});

it('saves a change, writes the audit log and sends the new text', function () {
    $this->actingAs(mbpUser(RoleKey::AdministrationStaff));

    $this->put(route('mailbuilder.system.update', 'access_requests.received'), ['doc' => json_encode(mbpDoc('<p>Willkommen im Team! {nummer}</p>')), 'subject' => 'Los geht\'s', 'preheader' => 'Kurz'])->assertRedirect(route('mailbuilder.system.edit', 'access_requests.received'))->assertSessionHas('success');

    $stored = MailTemplate::query()->where('system_key', 'access_requests.received')->firstOrFail();

    expect($stored->subject)->toBe('Los geht\'s')->and($stored->preheader)->toBe('Kurz')->and($stored->blocks['blocks'][0]['props']['html'])->toContain('Willkommen im Team');
    $this->assertDatabaseHas('audit_entries', ['event' => 'mailing.system_changed']);

    $mail = new AccessRequestReceivedMail('Anna', 1042);

    expect($mail->envelope()->subject)->toBe('Los geht\'s')->and($mail->render())->toContain('Willkommen im Team! 1042');
});

it('refuses a change that drops a protected placeholder or uses an unknown one', function () {
    $this->actingAs(mbpUser(RoleKey::Administration));

    $this->put(route('mailbuilder.system.update', 'access.password_reset'), ['doc' => json_encode(mbpDoc('<p>Ohne Link</p>')), 'subject' => 'Betreff'])->assertSessionHasErrors('doc');
    $this->put(route('mailbuilder.system.update', 'access.password_reset'), ['doc' => json_encode(mbpDoc('<p>{url} und {quatsch}</p>')), 'subject' => 'Betreff'])->assertSessionHasErrors('doc');

    expect(MailTemplate::query()->whereNotNull('system_key')->count())->toBe(0);
});

it('keeps earlier versions, restores them and resets to the default', function () {
    $this->actingAs(mbpUser(RoleKey::AdministrationStaff));
    $key = 'access_requests.received';

    $this->put(route('mailbuilder.system.update', $key), ['doc' => json_encode(mbpDoc('<p>Erste Fassung {nummer}</p>')), 'subject' => 'Eins']);
    $this->put(route('mailbuilder.system.update', $key), ['doc' => json_encode(mbpDoc('<p>Zweite Fassung {nummer}</p>')), 'subject' => 'Zwei']);

    $template = MailTemplate::query()->where('system_key', $key)->firstOrFail();
    $version = $template->versions()->firstOrFail();

    expect($template->subject)->toBe('Zwei')->and($version->subject)->toBe('Eins');

    $this->post(route('mailbuilder.system.restore', [$key, $version]))->assertSessionHas('success');
    expect($template->fresh()->subject)->toBe('Eins');

    $this->post(route('mailbuilder.system.reset', $key))->assertSessionHas('success');
    expect(MailTemplate::query()->where('system_key', $key)->exists())->toBeFalse();
});

it('previews with sample values and sends a test mail to oneself', function () {
    Mail::fake();
    $user = mbpUser(RoleKey::AdministrationStaff);
    $this->actingAs($user);

    $response = $this->postJson(route('mailbuilder.system.preview', 'access_requests.received'), ['doc' => json_encode(mbpDoc('<p>Hallo {nummer}</p>')), 'subject' => 'Betreff {offen}', 'dark' => true]);

    $response->assertOk()->assertJsonPath('subject', 'Betreff {offen}');
    expect($response->json('html'))->toContain('1042')->and($response->json('text'))->toContain('Hallo')
        ->and(collect($response->json('issues'))->pluck('message')->implode(' '))->toContain('{offen}');

    $this->postJson(route('mailbuilder.system.test', 'access_requests.received'), ['doc' => json_encode(mbpDoc('<p>Hallo {nummer}</p>')), 'subject' => 'Betreff'])->assertOk();
    Mail::assertSent(BuilderTestMail::class, fn (BuilderTestMail $mail): bool => $mail->hasTo($user->email) && $mail->envelope()->subject === '[Test] Betreff');
});

it('saves the design and shows it in every mail', function () {
    $this->actingAs(mbpUser(RoleKey::AdministrationStaff));

    $this->get(route('mailbuilder.design.edit'))->assertOk()->assertSee('Mail-Design');
    $this->put(route('mailbuilder.design.update'), ['accent' => '#58275a', 'button' => '#0b3a5a', 'background' => '#123456', 'footer_html' => '<p>Neuer Fuß</p>'])->assertRedirect(route('mailbuilder.design.edit'));

    $layout = MailLayout::current();

    expect($layout->settings['accent'])->toBe('#58275a')->and($layout->settings['button'])->toBe('#0b3a5a')->and($layout->settings['background'])->toBe(LayoutSettings::DEFAULTS['background'])->and($layout->settings['footer_html'])->toContain('Neuer Fuß');
    $this->assertDatabaseHas('audit_entries', ['event' => 'mailing.design_changed']);

    $html = (new AccountDeletedMail)->render();

    expect($html)->toContain('#58275a')->toContain('Neuer Fuß');

    $this->postJson(route('mailbuilder.design.preview'), ['accent' => '#0a7fa0'])->assertOk()->assertJsonStructure(['html', 'issues']);
});

it('uploads images and refuses to delete one that is still in use', function () {
    $this->actingAs(mbpUser(RoleKey::AdministrationStaff));

    $this->post(route('mailbuilder.assets.store'), ['file' => UploadedFile::fake()->image('logo.png', 200, 60), 'alt' => 'Vereinslogo'])->assertRedirect(route('mailbuilder.assets.index'));

    $asset = MailAsset::query()->firstOrFail();
    $this->get(route('mailbuilder.assets.index'))->assertOk()->assertSee('logo.png');
    $this->post(route('mailbuilder.assets.store'), ['file' => UploadedFile::fake()->create('x.svg', 4, 'image/svg+xml')])->assertSessionHasErrors('file');

    $this->put(route('mailbuilder.design.update'), ['logo_asset_id' => $asset->id, 'logo_alt' => 'Logo']);
    $this->delete(route('mailbuilder.assets.destroy', $asset))->assertSessionHasErrors('file');
    expect(MailAsset::query()->count())->toBe(1);

    $this->put(route('mailbuilder.design.update'), ['logo_asset_id' => '']);
    $this->delete(route('mailbuilder.assets.destroy', $asset))->assertSessionHas('success');
    expect(MailAsset::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($asset->path);
});
