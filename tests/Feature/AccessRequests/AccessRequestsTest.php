<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\AccountDeletion;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessRequests\Enums\AccessRequestStatus;
use App\Modules\AccessRequests\Mail\AccessRequestNoticeMail;
use App\Modules\AccessRequests\Mail\AccessRequestReceivedMail;
use App\Modules\AccessRequests\Mail\AccessRequestRejectedMail;
use App\Modules\AccessRequests\Mail\NewAccessRequestMail;
use App\Modules\AccessRequests\Models\AccessRequest;
use App\Modules\AccessRequests\Models\AccessRequestFile;
use App\Modules\AccessRequests\Services\AccessRequests;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

function arUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function arForm(array $overrides = []): array
{
    return $overrides + [
        'consent' => '1', 'connection' => 'team', 'organization' => 'Gesamtschule Musterstadt', 'details' => 'Ich leite SV-Workshops.',
        'first_name' => 'Tina', 'last_name' => 'Teamerin', 'email' => 'Tina@Beispiel.example', 'birth_date' => '1999-04-12', 'phone' => '0341 123456', 'terms' => '1',
    ];
}

function arRequest(array $overrides = []): AccessRequest
{
    return AccessRequest::query()->create($overrides + [
        'status' => AccessRequestStatus::New, 'connection' => 'team', 'first_name' => 'Tina', 'last_name' => 'Teamerin', 'email' => 'tina@beispiel.example',
        'birth_date' => '1999-04-12', 'consented_at' => now(), 'terms_version' => '2026-01',
    ]);
}

// ======================= Öffentliches Formular =======================

it('shows the access page with the login links and the stepwise request form to guests', function () {
    $html = $this->get(route('portal.access'))->assertOk()->getContent();

    expect($html)->toContain('Zur Anmeldung')->toContain('Passwort vergessen')
        ->toContain('data-vdbs-wizard')
        ->toContain('Einwilligung')->toContain('Verbindung zum Verein')->toContain('Kontaktdaten')->toContain('Prüfen und absenden')
        ->toContain('Antrag absenden')
        ->toContain('name="proofs[]"')
        ->toContain('Vereinsmitglieder')->toContain('wenden sich bitte an den Vorstand')
        ->and(substr_count($html, 'data-vdbs-step '))->toBe(4);

    foreach (['Teamer:in', 'Schüler:in', 'Lehrkraft', 'Partner:in'] as $option) {
        expect($html)->toContain($option);
    }

    expect($html)->not->toContain('<option value="member"')->not->toContain('>Vereinsmitglied<');
});

it('hides the request form from logged in users', function () {
    $this->actingAs(arUser(RoleKey::Member))->get(route('portal.access'))->assertOk()->assertDontSee('data-vdbs-wizard', false)->assertSee('Zu Ihrem Konto');
});

it('stores a request with proofs and confirms it by mail', function () {
    $this->post(route('portal.access.store'), arForm() + ['proofs' => [UploadedFile::fake()->create('nachweis.pdf', 100, 'application/pdf'), UploadedFile::fake()->create('ausweis.png', 50, 'image/png')]])
        ->assertRedirect(route('portal.access.thanks'))->assertSessionHasNoErrors();

    $request = AccessRequest::query()->firstOrFail();
    expect($request->status)->toBe(AccessRequestStatus::New)
        ->and($request->connection->value)->toBe('team')
        ->and($request->email)->toBe('tina@beispiel.example')
        ->and($request->first_name)->toBe('Tina')
        ->and($request->birth_date->toDateString())->toBe('1999-04-12')
        ->and($request->organization)->toBe('Gesamtschule Musterstadt')
        ->and($request->terms_version)->toBe('2026-01')
        ->and($request->consented_at)->not->toBeNull()
        ->and($request->files)->toHaveCount(2);

    foreach ($request->files as $file) {
        Storage::disk('local')->assertExists($file->path);
    }

    Mail::assertSent(AccessRequestReceivedMail::class, fn (AccessRequestReceivedMail $mail): bool => $mail->hasTo('tina@beispiel.example') && $mail->reference === $request->id && $mail->name === 'Tina Teamerin');

    $this->get(route('portal.access.thanks'))->assertOk()->assertSee('Vielen Dank für Ihren Antrag');
});

it('works without proofs and optional fields', function () {
    $this->post(route('portal.access.store'), arForm(['organization' => '', 'details' => '', 'phone' => '']))->assertSessionHasNoErrors();

    $request = AccessRequest::query()->firstOrFail();
    expect($request->organization)->toBeNull()->and($request->details)->toBeNull()->and($request->phone)->toBeNull()->and($request->files)->toHaveCount(0);
});

it('validates the request', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

    $this->post(route('portal.access.store'), [])->assertSessionHasErrors(['consent', 'connection', 'first_name', 'last_name', 'email', 'birth_date', 'terms']);
    $this->post(route('portal.access.store'), arForm(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post(route('portal.access.store'), arForm(['terms' => null]))->assertSessionHasErrors('terms');
    $this->post(route('portal.access.store'), arForm(['connection' => 'member']))->assertSessionHasErrors('connection');
    $this->post(route('portal.access.store'), arForm(['email' => 'kaputt']))->assertSessionHasErrors('email');
    $this->post(route('portal.access.store'), arForm(['birth_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('birth_date');
    $this->post(route('portal.access.store'), arForm(['phone' => 'abc']))->assertSessionHasErrors('phone');
    $this->post(route('portal.access.store'), arForm(['proofs' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]]))->assertSessionHasErrors('proofs.0');
    $this->post(route('portal.access.store'), arForm(['proofs' => [UploadedFile::fake()->create('gross.pdf', 6000, 'application/pdf')]]))->assertSessionHasErrors('proofs.0');
    $this->post(route('portal.access.store'), arForm(['proofs' => array_map(fn ($i) => UploadedFile::fake()->create("n{$i}.pdf", 10, 'application/pdf'), range(1, 6))]))->assertSessionHasErrors('proofs');

    expect(AccessRequest::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

it('answers a bot like normal and creates nothing', function () {
    $this->post(route('portal.access.store'), arForm(['website' => 'http://spam.example']))->assertRedirect(route('portal.access.thanks'));

    expect(AccessRequest::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

it('limits requests per visitor', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('portal.access.store'), arForm(['email' => "person{$i}@beispiel.example"]))->assertRedirect(route('portal.access.thanks'));
    }

    $this->post(route('portal.access.store'), arForm(['email' => 'person6@beispiel.example']))->assertStatus(429);
});

it('answers neutrally and creates nothing when the address has an account or an open request', function () {
    arUser();
    $existing = User::factory()->create(['email' => 'tina@beispiel.example']);

    $this->post(route('portal.access.store'), arForm())->assertRedirect(route('portal.access.thanks'));

    expect(AccessRequest::query()->count())->toBe(0);
    Mail::assertSent(AccessRequestNoticeMail::class, fn (AccessRequestNoticeMail $mail): bool => $mail->hasTo('tina@beispiel.example') && $mail->loginUrl === route('login'));
    Mail::assertNotSent(AccessRequestReceivedMail::class);

    $existing->delete();
    $this->post(route('portal.access.store'), arForm());
    $this->post(route('portal.access.store'), arForm());

    expect(AccessRequest::query()->count())->toBe(1);
    Mail::assertSent(AccessRequestNoticeMail::class, 2);
});

it('notifies the staff without personal data', function () {
    $staff = arUser(RoleKey::AdministrationStaff);
    arUser(RoleKey::Member);

    $this->post(route('portal.access.store'), arForm());
    $request = AccessRequest::query()->firstOrFail();

    Mail::assertSent(NewAccessRequestMail::class, fn (NewAccessRequestMail $mail): bool => $mail->hasTo($staff->email) && $mail->url === route('access-requests.show', $request));
    Mail::assertSent(NewAccessRequestMail::class, 1);

    config(['portal.access_requests.notify_staff' => false]);
    $this->post(route('portal.access.store'), arForm(['email' => 'zweite@beispiel.example']));
    Mail::assertSent(NewAccessRequestMail::class, 1);
});

// ======================= Geschäftsstelle =======================

it('shows the requests to staff and board only', function () {
    $request = arRequest();

    $this->get(route('access-requests.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(arUser($role))->get(route('access-requests.index'))->assertOk()->assertSee('Tina Teamerin');
        $this->get(route('access-requests.show', $request))->assertOk();
    }

    foreach ([RoleKey::Coordination, RoleKey::Member, RoleKey::Team, RoleKey::Administration] as $role) {
        $this->actingAs(arUser($role))->get(route('access-requests.index'))->assertForbidden();
        $this->get(route('access-requests.show', $request))->assertForbidden();
    }
});

it('lists requests with stats, search and status filter and open ones first', function () {
    arRequest(['first_name' => 'Anna', 'last_name' => 'Alt', 'email' => 'a@beispiel.example', 'status' => AccessRequestStatus::Rejected, 'decided_at' => now()]);
    arRequest(['first_name' => 'Bert', 'last_name' => 'Neu', 'email' => 'b@beispiel.example', 'organization' => 'Hauptschule Nord']);
    arRequest(['first_name' => 'Cora', 'last_name' => 'Prüfung', 'email' => 'c@beispiel.example', 'status' => AccessRequestStatus::InReview]);

    $html = $this->actingAs(arUser(RoleKey::AdministrationStaff))->get(route('access-requests.index'))->assertOk()->getContent();
    expect(strpos($html, 'Bert Neu'))->toBeLessThan(strpos($html, 'Anna Alt'));

    $this->get(route('access-requests.index', ['q' => 'Hauptschule']))->assertSee('Bert Neu')->assertDontSee('Cora Prüfung');
    $this->get(route('access-requests.index', ['status' => 'in_review']))->assertSee('Cora Prüfung')->assertDontSee('Bert Neu');
    $this->get(route('access-requests.index'))->assertSee('<a class="vdbs-table__row-link" href="'.route('access-requests.show', AccessRequest::query()->where('first_name', 'Bert')->first()).'">', false);
});

it('shows the details and the proofs of a request and delivers them privately', function () {
    $staff = arUser(RoleKey::AdministrationStaff);
    $this->post(route('portal.access.store'), arForm() + ['proofs' => [UploadedFile::fake()->create('nachweis.pdf', 100, 'application/pdf')]]);
    $request = AccessRequest::query()->firstOrFail();
    $file = $request->files->first();

    $this->actingAs($staff)->get(route('access-requests.show', $request))->assertOk()
        ->assertSee('Tina Teamerin')->assertSee('tina@beispiel.example')->assertSee('12.04.1999')->assertSee('Gesamtschule Musterstadt')->assertSee('Ich leite SV-Workshops.')
        ->assertSee('Teamer:in')->assertSee('nachweis.pdf')->assertSee('Annehmen')->assertSee('Ablehnen');

    $this->get(route('access-requests.files.download', [$request, $file]))->assertOk();

    $other = arRequest(['email' => 'andere@beispiel.example']);
    $this->get(route('access-requests.files.download', [$other, $file]))->assertNotFound();

    $this->actingAs(arUser(RoleKey::Member))->get(route('access-requests.files.download', [$request, $file]))->assertForbidden();
    $this->app['auth']->forgetGuards();
    $this->get(route('access-requests.files.download', [$request, $file]))->assertRedirect(route('login'));
});

it('puts a request into review', function () {
    $request = arRequest();

    $this->actingAs(arUser(RoleKey::AdministrationStaff))->post(route('access-requests.review', $request))->assertRedirect(route('access-requests.show', $request));

    expect($request->fresh()->status)->toBe(AccessRequestStatus::InReview);
});

it('approves a request: account with invitation, team role with onboarding, proofs gone', function () {
    $staff = arUser(RoleKey::AdministrationStaff);
    $this->post(route('portal.access.store'), arForm() + ['proofs' => [UploadedFile::fake()->create('nachweis.pdf', 100, 'application/pdf')]]);
    $request = AccessRequest::query()->firstOrFail();
    $path = $request->files->first()->path;

    $this->actingAs($staff)->post(route('access-requests.approve', $request), ['role' => 'team', 'note' => 'Bekannt aus dem Workshop'])->assertRedirect(route('access-requests.show', $request))->assertSessionHas('success');

    $request->refresh();
    $user = User::query()->where('email', 'tina@beispiel.example')->firstOrFail();

    expect($request->status)->toBe(AccessRequestStatus::Approved)
        ->and($request->user_id)->toBe($user->id)
        ->and($request->decided_by_user_id)->toBe($staff->id)
        ->and($request->decision_note)->toBe('Bekannt aus dem Workshop')
        ->and($request->files()->count())->toBe(0)
        ->and($user->status)->toBe(UserStatus::PendingVerification)
        ->and($user->person->fullName())->toBe('Tina Teamerin')
        ->and($user->person->birth_date->toDateString())->toBe('1999-04-12')
        ->and($user->activeRoleAssignments()->whereHas('role', fn ($role) => $role->where('key', 'team'))->exists())->toBeTrue()
        ->and(TeamOnboarding::query()->where('user_id', $user->id)->exists())->toBeTrue();

    Storage::disk('local')->assertMissing($path);
    Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail): bool => $mail->hasTo('tina@beispiel.example'));

    $entry = AuditEntry::query()->where('event', 'access_request.approved')->firstOrFail();
    expect($entry->context)->toEqual(['rolle' => 'team']);

    $this->get(route('access-requests.show', $request))->assertSee('Angenommen')->assertSee('Bekannt aus dem Workshop')->assertSee('Die Nachweise wurden mit der Entscheidung gelöscht');
});

it('approves without a role and links an existing person without an account', function () {
    $person = Person::factory()->create(['email' => 'tina@beispiel.example']);
    $request = arRequest(['connection' => 'teacher']);

    $this->actingAs(arUser(RoleKey::BoardMember))->post(route('access-requests.approve', $request), [])->assertSessionHas('success');

    $user = User::query()->where('email', 'tina@beispiel.example')->firstOrFail();
    expect($user->person_id)->toBe($person->id)->and($user->activeRoleAssignments()->count())->toBe(0)->and(Person::query()->where('email', 'tina@beispiel.example')->count())->toBe(1);
    Mail::assertSent(InvitationMail::class, 1);
});

it('refuses to approve when the address already has an account or the role is unknown', function () {
    $request = arRequest();
    User::factory()->create(['email' => 'tina@beispiel.example']);

    $staff = arUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff)->post(route('access-requests.approve', $request), [])->assertSessionHas('warning');
    expect($request->fresh()->status)->toBe(AccessRequestStatus::New);

    $this->post(route('access-requests.approve', arRequest(['email' => 'neu@beispiel.example'])), ['role' => 'administration'])->assertSessionHasErrors('role');
});

it('rejects a request with a reason by mail and deletes the proofs', function () {
    $this->post(route('portal.access.store'), arForm() + ['proofs' => [UploadedFile::fake()->create('nachweis.pdf', 100, 'application/pdf')]]);
    $request = AccessRequest::query()->firstOrFail();
    $path = $request->files->first()->path;

    $this->actingAs(arUser(RoleKey::AdministrationStaff))->post(route('access-requests.reject', $request), ['note' => 'Keine Verbindung zum Verein erkennbar'])->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(AccessRequestStatus::Rejected)->and($request->files()->count())->toBe(0)->and(User::query()->where('email', 'tina@beispiel.example')->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($path);
    Mail::assertSent(AccessRequestRejectedMail::class, fn (AccessRequestRejectedMail $mail): bool => $mail->hasTo('tina@beispiel.example') && $mail->note === 'Keine Verbindung zum Verein erkennbar');
    expect(AuditEntry::query()->where('event', 'access_request.rejected')->exists())->toBeTrue();
});

it('does not decide a request twice and lets only managers decide', function () {
    $request = arRequest(['status' => AccessRequestStatus::Rejected, 'decided_at' => now()]);

    $this->actingAs(arUser(RoleKey::AdministrationStaff))->post(route('access-requests.approve', $request), [])->assertSessionHas('warning');
    $this->post(route('access-requests.reject', $request), [])->assertSessionHas('warning');
    expect($request->fresh()->status)->toBe(AccessRequestStatus::Rejected);

    $open = arRequest(['email' => 'offen@beispiel.example']);
    foreach ([RoleKey::Coordination, RoleKey::Member] as $role) {
        $this->actingAs(arUser($role))->post(route('access-requests.approve', $open), [])->assertForbidden();
        $this->post(route('access-requests.reject', $open), [])->assertForbidden();
        $this->post(route('access-requests.review', $open))->assertForbidden();
    }
});

it('keeps single requests out of the general log except the decision', function () {
    $this->post(route('portal.access.store'), arForm());

    expect(AuditEntry::query()->count())->toBe(0);
});

// ======================= Aufbewahrung =======================

it('deletes decided requests after the retention period and stale open ones, with their files', function () {
    config(['portal.access_requests.retention_days' => 30, 'portal.access_requests.open_retention_days' => 100]);
    $old = arRequest(['email' => 'alt@beispiel.example', 'status' => AccessRequestStatus::Rejected, 'decided_at' => now()->subDays(31)]);
    $recent = arRequest(['email' => 'neu@beispiel.example', 'status' => AccessRequestStatus::Approved, 'decided_at' => now()->subDays(5)]);
    $stale = arRequest(['email' => 'liegt@beispiel.example']);
    $stale->forceFill(['created_at' => now()->subDays(101)])->save();
    $fresh = arRequest(['email' => 'frisch@beispiel.example']);

    Storage::disk('local')->put('access-requests/'.$stale->id.'/x.pdf', 'x');
    $stale->files()->create(['original_name' => 'x.pdf', 'path' => 'access-requests/'.$stale->id.'/x.pdf', 'mime' => 'application/pdf', 'size' => 1]);

    $this->artisan('access-requests:prune')->assertSuccessful();

    expect(AccessRequest::query()->pluck('email')->all())->toEqualCanonicalizing(['neu@beispiel.example', 'frisch@beispiel.example']);
    Storage::disk('local')->assertMissing('access-requests/'.$stale->id.'/x.pdf');
    expect(AccessRequestFile::query()->count())->toBe(0);
});

it('is scheduled daily', function () {
    expect(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('access-requests:prune')->daily()");
});

it('deletes requests of an address when the account is deleted', function () {
    $user = arUser();
    arRequest(['email' => mb_strtolower($user->email)]);
    arRequest(['email' => 'unbeteiligt@beispiel.example']);

    app(AccountDeletion::class)->anonymize($user->fresh());

    expect(AccessRequest::query()->pluck('email')->all())->toBe(['unbeteiligt@beispiel.example']);
});

it('shows the access requests in the area Geschäftsstelle', function () {
    $this->actingAs(arUser(RoleKey::AdministrationStaff))->get(route('access-requests.index'))->assertOk()->assertSee('Zugangsanträge')->assertSee('Artikel');
});
