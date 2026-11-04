<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Contacts\Mail\ContactAccessMail;
use App\Modules\Contacts\Models\AccessCredential;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\AccessCredentials;
use App\Modules\Contacts\Services\ContactImporter;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function kcUser(?RoleKey $role = RoleKey::AdministrationStaff): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function kcContact(string $email = 'kontakt@example.org', array $extra = []): TeamContact
{
    return TeamContact::query()->create(['email' => $email] + $extra);
}

it('checks a list without saving anything', function () {
    kcContact('vorhanden@example.org');

    $preview = app(ContactImporter::class)->preview("E-Mail;Vorname\nneu@example.org;Anna\nvorhanden@example.org;Bea\nNEU@example.org;Doppelt\n");

    expect($preview['errors'])->toBe([])
        ->and($preview['counts'])->toMatchArray(['total' => 3, 'new' => 1, 'existing' => 1, 'duplicates' => 1])
        ->and(TeamContact::query()->count())->toBe(1);
});

it('reads a plain list with one address per line and the template columns', function () {
    $user = kcUser();

    $result = app(ContactImporter::class)->import("eins@example.org\nzwei@example.org;Zoe;Zander;030 1234\n", $user);

    expect($result['errors'])->toBe([])->and($result['created'])->toBe(2)
        ->and(TeamContact::query()->where('email', 'zwei@example.org')->first())->first_name->toBe('Zoe')->last_name->toBe('Zander')->phone->toBe('030 1234');
});

it('imports nothing and names every faulty line', function () {
    $result = app(ContactImporter::class)->import("E-Mail\ngut@example.org\nkaputt\nauch kaputt@\n", kcUser());

    expect($result['created'])->toBe(0)->and($result['errors'])->toHaveCount(2)->and($result['errors'][0])->toContain('Zeile 3')
        ->and(TeamContact::query()->count())->toBe(0);
});

it('does not overwrite existing data but fills empty fields', function () {
    kcContact('a@example.org', ['first_name' => 'Alt']);

    $result = app(ContactImporter::class)->import("E-Mail;Vorname;Nachname\na@example.org;Neu;Müller\n", kcUser());

    $contact = TeamContact::query()->where('email', 'a@example.org')->first();
    expect($result['updated'])->toBe(1)->and($contact->first_name)->toBe('Alt')->and($contact->last_name)->toBe('Müller');
});

it('rejects unknown columns and empty lists', function () {
    $importer = app(ContactImporter::class);

    expect($importer->preview("E-Mail;Geburtstag\na@example.org;1.1.2000\n")['errors'][0])->toContain('Unbekannte Spalte')
        ->and($importer->preview("  \n")['errors'][0])->toContain('leer');
});

it('limits the import to the maximum number of rows', function () {
    $list = "E-Mail\n".implode("\n", array_map(fn (int $i): string => "p{$i}@example.org", range(1, ContactImporter::MAX_ROWS + 1)));

    expect(app(ContactImporter::class)->preview($list)['errors'][0])->toContain('mehr als');
});

it('marks blocked and unsubscribed addresses in the preview but still imports them', function () {
    app(MailSuppressions::class)->suppress('gesperrt@example.org', 'manual', null, null);
    app(MailUnsubscribes::class)->unsubscribe('abgemeldet@example.org', 'team_mailings');

    $preview = app(ContactImporter::class)->preview("E-Mail\ngesperrt@example.org\nabgemeldet@example.org\nfrei@example.org\n");

    expect($preview['counts']['blocked'])->toBe(2);
});

it('previews and imports through the pages with the consent and logs only numbers', function () {
    $user = kcUser();

    $this->actingAs($user)->post(route('contacts.import.preview'), ['list' => "a@example.org\nb@example.org", 'consent' => '0'])->assertSessionHasErrors('consent');
    $this->post(route('contacts.import.preview'), ['list' => "a@example.org\nb@example.org", 'consent' => '1'])->assertOk()->assertSee('a@example.org');
    expect(TeamContact::query()->count())->toBe(0);

    $this->post(route('contacts.import.store'), ['list' => "a@example.org\nb@example.org"])->assertSessionHasErrors('consent');
    $this->post(route('contacts.import.store'), ['list' => "a@example.org\nb@example.org", 'consent' => '1'])->assertRedirect(route('contacts.index'));

    expect(TeamContact::query()->count())->toBe(2)->and(TeamContact::query()->first()->consent_confirmed_at)->not->toBeNull();

    $entry = AuditEntry::query()->where('event', 'contacts.imported')->first();
    expect($entry)->not->toBeNull()->and(json_encode($entry->context))->not->toContain('example.org');
});

it('accepts an uploaded file and offers the template', function () {
    $user = kcUser();
    $file = UploadedFile::fake()->createWithContent('liste.csv', "E-Mail;Vorname\nx@example.org;Xaver\n");

    $this->actingAs($user)->post(route('contacts.import.preview'), ['file' => $file, 'consent' => '1'])->assertOk()->assertSee('x@example.org');
    $this->get(route('contacts.import.template'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('keeps the contact pages for the office and the board', function () {
    $this->get(route('contacts.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Coordination, RoleKey::Team, RoleKey::Member, null] as $role) {
        $this->actingAs(kcUser($role))->get(route('contacts.index'))->assertForbidden();
    }

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(kcUser($role))->get(route('contacts.index'))->assertOk();
    }
});

it('lists contacts with their access state and finds them', function () {
    $user = kcUser();
    $contact = kcContact('suche@example.org', ['first_name' => 'Susi', 'last_name' => 'Suche']);
    kcContact('andere@example.org');

    $this->actingAs($user)->get(route('contacts.index'))->assertOk()->assertSee('suche@example.org')->assertSee('andere@example.org');
    $this->get(route('contacts.index', ['q' => 'Susi']))->assertOk()->assertSee('suche@example.org')->assertDontSee('andere@example.org');
    $this->get(route('contacts.index', ['zugang' => ['keiner']]))->assertOk()->assertSee('suche@example.org');

    expect($contact->displayName())->toBe('Susi Suche');
});

it('stores only hashes of the link and the code', function () {
    $contact = kcContact();

    $issued = app(AccessCredentials::class)->issue($contact);
    $row = AccessCredential::query()->first();

    expect($issued['token'])->toHaveLength(40)->and($issued['code'])->toMatch('/^[A-Z2-9]{5}-[A-Z2-9]{5}$/')
        ->and($row->token_hash)->toBe(hash('sha256', $issued['token']))
        ->and(json_encode($row->getAttributes()))->not->toContain($issued['token'])->and(json_encode($row->getAttributes()))->not->toContain(str_replace('-', '', $issued['code']))
        ->and($row->code_hint)->toBe(substr(str_replace('-', '', $issued['code']), -2));
});

it('logs in with the link and with email and code', function () {
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);

    $this->get(route('access.link', ['token' => $issued['token']]))->assertRedirect(route('access.home'));
    $this->get(route('access.home'))->assertOk()->assertSee($contact->email);

    $this->post(route('access.logout'))->assertRedirect(route('home'));
    $this->get(route('access.home'))->assertRedirect(route('access.code'));

    $this->post(route('access.verify'), ['email' => 'KONTAKT@example.org', 'code' => strtolower($issued['code'])])->assertRedirect(route('access.home'));
    $this->get(route('access.home'))->assertOk();
    expect(AccessCredential::query()->first())->uses->toBe(2)->last_used_at->not->toBeNull();
});

it('gives the same answer for a wrong link, a wrong code and an unknown address', function () {
    $contact = kcContact();
    app(AccessCredentials::class)->issue($contact);

    $this->get(route('access.link', ['token' => str_repeat('a', 40)]))->assertRedirect(route('access.code'))->assertSessionHasErrors('code');
    $this->post(route('access.verify'), ['email' => $contact->email, 'code' => 'AAAAA-AAAAA'])->assertSessionHasErrors('code');
    $this->post(route('access.verify'), ['email' => 'unbekannt@example.org', 'code' => 'AAAAA-AAAAA'])->assertSessionHasErrors('code');
});

it('blocks the code entry for an address after five wrong tries, even with the right code', function () {
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);
    RateLimiter::clear('contact-code:'.sha1($contact->email));

    foreach (range(1, 5) as $i) {
        $this->post(route('access.verify'), ['email' => $contact->email, 'code' => 'ZZZZZ-ZZZZ'.$i]);
    }

    $this->post(route('access.verify'), ['email' => $contact->email, 'code' => $issued['code']])->assertSessionHasErrors('code');
    $this->get(route('access.home'))->assertRedirect(route('access.code'));
});

it('does not accept revoked or expired access', function () {
    $contact = kcContact();
    $service = app(AccessCredentials::class);
    $issued = $service->issue($contact);

    $this->get(route('access.link', ['token' => $issued['token']]))->assertRedirect(route('access.home'));
    $service->revokeAll($contact);
    $this->get(route('access.home'))->assertRedirect(route('access.code'));
    $this->get(route('access.link', ['token' => $issued['token']]))->assertSessionHasErrors('code');

    $second = $service->issue($contact);
    AccessCredential::query()->latest('id')->first()->update(['expires_at' => now()->subMinute()]);
    $this->get(route('access.link', ['token' => $second['token']]))->assertSessionHasErrors('code');
});

it('ends the session after the idle time', function () {
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);

    $this->get(route('access.link', ['token' => $issued['token']]));
    $this->get(route('access.home'))->assertOk();

    $this->travel(31)->minutes();
    $this->get(route('access.home'))->assertRedirect(route('access.code'));
});

it('keeps an older access valid for the grace time and then lets it expire', function () {
    $contact = kcContact();
    $service = app(AccessCredentials::class);
    $old = $service->issue($contact);
    $new = $service->issue($contact);

    expect($service->findByToken($old['token']))->not->toBeNull()->and($service->findByToken($new['token']))->not->toBeNull();

    $this->travel(15)->days();
    expect($service->findByToken($old['token']))->toBeNull();
});

it('opens only the pages for applications and no area of the portal', function () {
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);
    $this->get(route('access.link', ['token' => $issued['token']]));

    $this->get(route('team.home'))->assertRedirect(route('login'));
    $this->get(route('seminars.index'))->assertRedirect(route('login'));
    $this->get(route('contacts.index'))->assertRedirect(route('login'));
});

it('saves the details only with the tick and otherwise only for the visit', function () {
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);
    $this->get(route('access.link', ['token' => $issued['token']]));

    $this->post(route('access.profile'), ['first_name' => '', 'last_name' => ''])->assertSessionHasErrors(['first_name', 'last_name']);

    $this->post(route('access.profile'), ['first_name' => 'Nur', 'last_name' => 'Besuch', 'city' => 'Berlin'])->assertSessionHas('contact_access.draft');
    expect($contact->fresh()->first_name)->toBeNull();

    $this->post(route('access.profile'), ['first_name' => 'Dauer', 'last_name' => 'Haft', 'street' => 'Weg', 'save' => '1']);
    expect($contact->fresh())->first_name->toBe('Dauer')->street->toBe('Weg')->profile_saved->toBeTrue();
});

it('sends the access mail with link and code and stores nothing in clear', function () {
    $user = kcUser();
    $contact = kcContact('post@example.org', ['first_name' => 'Pia']);

    $this->actingAs($user)->post(route('contacts.send', $contact))->assertRedirect(route('contacts.index'))->assertSessionHas('success');

    Mail::assertSent(ContactAccessMail::class, function (ContactAccessMail $mail): bool {
        $mail->assertSeeInHtml('/z/');
        $mail->assertHasSubject('Ihr persönlicher Zugang für Seminarbewerbungen');

        return $mail->hasTo('post@example.org') && str_contains($mail->accessLink, '/z/') && preg_match('/^[A-Z2-9]{5}-[A-Z2-9]{5}$/', $mail->accessCode) === 1;
    });
    expect(AccessCredential::query()->first()->sent_at)->not->toBeNull();
});

it('sends different links and codes to different people', function () {
    $user = kcUser();
    $a = kcContact('a@example.org');
    $b = kcContact('b@example.org');

    $this->actingAs($user)->post(route('contacts.send', $a));
    $this->post(route('contacts.send', $b));

    $sent = Mail::sent(ContactAccessMail::class);
    expect($sent)->toHaveCount(2)->and($sent[0]->accessLink)->not->toBe($sent[1]->accessLink)->and($sent[0]->accessCode)->not->toBe($sent[1]->accessCode);
});

it('sends nothing to blocked or unsubscribed addresses', function () {
    $user = kcUser();
    app(MailSuppressions::class)->suppress('gesperrt@example.org', 'manual', null, null);
    app(MailUnsubscribes::class)->unsubscribe('abgemeldet@example.org', 'team_mailings');
    $blocked = kcContact('gesperrt@example.org');
    $unsubscribed = kcContact('abgemeldet@example.org');
    kcContact('frei@example.org');

    $this->actingAs($user)->post(route('contacts.send', $blocked))->assertSessionHas('warning');
    $this->post(route('contacts.send', $unsubscribed))->assertSessionHas('warning');
    $this->post(route('contacts.send-all'))->assertSessionHas('success');

    Mail::assertSent(ContactAccessMail::class, 1);
    Mail::assertSent(ContactAccessMail::class, fn (ContactAccessMail $mail): bool => $mail->hasTo('frei@example.org'));
});

it('sends to all without an access once and not again', function () {
    $user = kcUser();
    kcContact('a@example.org');
    kcContact('b@example.org');

    $this->actingAs($user)->post(route('contacts.send-all'));
    $this->post(route('contacts.send-all'));

    Mail::assertSent(ContactAccessMail::class, 2);
});

it('sends a new access on request with the same answer for known and unknown addresses', function () {
    kcContact('bekannt@example.org');

    $known = $this->post(route('access.resend'), ['email' => 'bekannt@example.org']);
    $knownMessage = session('success');
    $unknown = $this->post(route('access.resend'), ['email' => 'fremd@example.org']);

    $known->assertRedirect(route('access.code'));
    $unknown->assertRedirect(route('access.code'));
    expect($knownMessage)->not->toBeNull()->and(session('success'))->toBe($knownMessage);
    Mail::assertSent(ContactAccessMail::class, 1);
});

it('revokes an access and deletes a contact with its credentials', function () {
    $user = kcUser();
    $contact = kcContact();
    $issued = app(AccessCredentials::class)->issue($contact);

    $this->actingAs($user)->post(route('contacts.revoke', $contact))->assertSessionHas('success');
    expect(app(AccessCredentials::class)->findByToken($issued['token']))->toBeNull();

    $this->delete(route('contacts.destroy', $contact))->assertRedirect(route('contacts.index'));
    expect(TeamContact::query()->count())->toBe(0)->and(AccessCredential::query()->count())->toBe(0);
});

it('shows open seminars read-only on the access page', function () {
    $contact = kcContact();
    $seminar = \App\Modules\Seminars\Models\Seminar::factory()->create(['title' => 'Offenes Seminar', 'status' => 'scheduled', 'team_needed' => 3, 'application_deadline' => now()->addWeek()]);
    $issued = app(AccessCredentials::class)->issue($contact);

    $this->get(route('access.link', ['token' => $issued['token']]));
    $this->get(route('access.home'))->assertOk()->assertSee('Offenes Seminar')->assertSee($seminar->number);
});

it('filters by several access states at once', function () {
    $user = kcUser();
    $none = kcContact('keiner@example.org');
    $used = kcContact('benutzt@example.org');
    $revoked = kcContact('gesperrt@example.org');
    $service = app(AccessCredentials::class);
    $service->issue($used)['credential']->forceFill(['sent_at' => now(), 'last_used_at' => now()])->save();
    $service->issue($revoked);
    $service->revokeAll($revoked);

    $this->actingAs($user)->get(route('contacts.index', ['zugang' => ['benutzt', 'gesperrt']]))->assertOk()
        ->assertSee('benutzt@example.org')->assertSee('gesperrt@example.org')->assertDontSee('keiner@example.org');
    $this->get(route('contacts.index', ['zugang' => ['keiner', 'benutzt']]))->assertOk()->assertSee('keiner@example.org')->assertSee('benutzt@example.org')->assertDontSee('gesperrt@example.org');
});

it('sends and revokes the access for several ticked addresses at once', function () {
    $user = kcUser();
    $a = kcContact('a@example.org');
    $b = kcContact('b@example.org');
    kcContact('c@example.org');
    app(MailSuppressions::class)->suppress('b@example.org', 'manual', null, null);

    $this->actingAs($user)->post(route('contacts.send-selected'), [])->assertSessionHasErrors('ids');
    $this->post(route('contacts.send-selected'), ['ids' => [$a->id, $b->id]])->assertSessionHas('success');

    Mail::assertSent(ContactAccessMail::class, 1);
    Mail::assertSent(ContactAccessMail::class, fn (ContactAccessMail $mail): bool => $mail->hasTo('a@example.org'));

    $this->post(route('contacts.revoke-selected'), ['ids' => [$a->id, $b->id]])->assertSessionHas('success');
    expect(AccessCredential::query()->valid()->count())->toBe(0);
});

it('offers a select-all box in the table head and large boxes in the rows', function () {
    $user = kcUser();
    kcContact('a@example.org');

    $this->actingAs($user)->get(route('contacts.index'))->assertOk()
        ->assertSee('data-vdbs-select-all="bulk-form"', false)
        ->assertSee('vdbs-table__select', false)
        ->assertSee('form="bulk-form"', false)
        ->assertSee('Auswahl löschen');

    expect(file_get_contents(resource_path('js/select-all.js')))->toContain('data-vdbs-select-all')
        ->and(file_get_contents(resource_path('js/app.js')))->toContain('./select-all')
        ->and(file_get_contents(resource_path('css/vdbs/components/data/table.css')))->toContain('.vdbs-table__select .vdbs-choice__input');
});

it('deletes several ticked contacts only after a confirmation page', function () {
    $user = kcUser();
    $a = kcContact('weg1@example.org');
    $b = kcContact('weg2@example.org');
    $c = kcContact('bleibt@example.org');
    app(AccessCredentials::class)->issue($a);

    $this->actingAs($user)->post(route('contacts.delete-selected'), [])->assertSessionHasErrors('ids');

    $this->post(route('contacts.delete-selected'), ['ids' => [$a->id, $b->id]])->assertOk()->assertSee('weg1@example.org')->assertSee('weg2@example.org')->assertSee('Endgültig löschen');
    expect(TeamContact::query()->count())->toBe(3);

    $this->post(route('contacts.delete-selected'), ['ids' => [$a->id, $b->id], 'confirmed' => '1'])->assertRedirect(route('contacts.index'))->assertSessionHas('success');
    expect(TeamContact::query()->pluck('email')->all())->toBe(['bleibt@example.org'])->and(AccessCredential::query()->count())->toBe(0);
});

it('keeps the bulk delete for the office and the board', function () {
    $contact = kcContact();

    $this->post(route('contacts.delete-selected'), ['ids' => [$contact->id], 'confirmed' => '1'])->assertRedirect(route('login'));
    $this->actingAs(kcUser(RoleKey::Coordination))->post(route('contacts.delete-selected'), ['ids' => [$contact->id], 'confirmed' => '1'])->assertForbidden();
    expect(TeamContact::query()->count())->toBe(1);
});

it('adds a single address with the consent and sends the access on request', function () {
    $user = kcUser();

    $this->actingAs($user)->get(route('contacts.create'))->assertOk()->assertSee('E-Mail-Adresse');
    $this->post(route('contacts.store'), ['email' => 'neu@example.org'])->assertSessionHasErrors('consent');

    $this->post(route('contacts.store'), ['email' => ' Neu@Example.org ', 'first_name' => 'Nora', 'consent' => '1', 'send_access' => '1'])->assertRedirect(route('contacts.index'))->assertSessionHas('success');

    $contact = TeamContact::query()->first();
    expect($contact->email)->toBe('neu@example.org')->and($contact->first_name)->toBe('Nora')->and($contact->consent_confirmed_at)->not->toBeNull();
    Mail::assertSent(ContactAccessMail::class, fn (ContactAccessMail $mail): bool => $mail->hasTo('neu@example.org'));
});

it('refuses a duplicate or invalid address when adding one', function () {
    $user = kcUser();
    kcContact('da@example.org');

    $this->actingAs($user)->post(route('contacts.store'), ['email' => 'DA@example.org', 'consent' => '1'])->assertSessionHasErrors('email');
    $this->post(route('contacts.store'), ['email' => 'kaputt', 'consent' => '1'])->assertSessionHasErrors('email');
    expect(TeamContact::query()->count())->toBe(1);
});

it('adds an address without sending the access and keeps the form for the office only', function () {
    $this->actingAs(kcUser())->post(route('contacts.store'), ['email' => 'still@example.org', 'consent' => '1'])->assertSessionHas('success');
    Mail::assertNothingSent();

    $this->actingAs(kcUser(RoleKey::Coordination))->get(route('contacts.create'))->assertForbidden();
});
