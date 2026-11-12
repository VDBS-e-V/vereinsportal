<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function mnBoard(): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('key', RoleKey::BoardMember->value)->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);

    return $user;
}

function mnPdf(string $name = 'antrag.pdf', int $kilobytes = 120): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
}

function mnForm(array $overrides = []): array
{
    return $overrides + [
        'first_name' => 'Erika',
        'last_name' => 'Musterfrau',
        'birth_date' => '1980-05-17',
        'email' => 'Erika@Example.org',
        'mode' => 'request',
        'kind' => 'application',
        'document' => mnPdf(),
        'comment' => 'Per Post eingegangen',
    ];
}

// ---------- Erfassen ohne digitale Person ----------

it('records a paper application without creating a person or an account', function () {
    $board = mnBoard();
    $persons = Person::query()->count();
    $users = User::query()->count();

    $this->actingAs($board)->post(route('membership.store'), mnForm())->assertRedirect()->assertSessionHas('success');

    $membership = Membership::query()->firstOrFail();

    expect($membership->person_id)->toBeNull()
        ->and($membership->status)->toBe(MembershipStatus::Requested)
        ->and($membership->source)->toBe('manual')
        ->and($membership->displayName())->toBe('Erika Musterfrau')
        ->and($membership->email)->toBe('erika@example.org')
        ->and($membership->birth_date->toDateString())->toBe('1980-05-17')
        ->and($membership->events()->where('type', 'requested')->first()->comment)->toBe('Per Post eingegangen')
        ->and(Person::query()->count())->toBe($persons)
        ->and(User::query()->count())->toBe($users);
});

it('records an existing membership with a joining date', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm(['mode' => 'admit', 'starts_on' => '2019-03-01', 'kind' => 'record']))->assertRedirect();

    $membership = Membership::query()->firstOrFail();

    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->starts_on->toDateString())->toBe('2019-03-01')
        ->and($membership->documents()->first()->kind)->toBe('record');
});

it('needs only name and document and validates the rest', function () {
    $this->actingAs(mnBoard());

    $this->post(route('membership.store'), mnForm(['birth_date' => null, 'email' => null, 'comment' => null]))->assertSessionHasNoErrors();
    expect(Membership::query()->count())->toBe(1);

    $this->post(route('membership.store'), [])->assertSessionHasErrors(['first_name', 'last_name', 'mode', 'kind', 'document']);
    $this->post(route('membership.store'), mnForm(['last_name' => 'Anders', 'mode' => 'admit', 'starts_on' => null]))->assertSessionHasErrors('starts_on');
    $this->post(route('membership.store'), mnForm(['last_name' => 'Zweitens', 'email' => 'kein-mail']))->assertSessionHasErrors('email');
    $this->post(route('membership.store'), mnForm(['last_name' => 'Drittens', 'birth_date' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('birth_date');
    $this->post(route('membership.store'), mnForm(['last_name' => 'Viertens', 'kind' => 'unsinn']))->assertSessionHasErrors('kind');
});

it('refuses a second open membership for the same name and birth date or the same email', function () {
    $this->actingAs(mnBoard());

    $this->post(route('membership.store'), mnForm())->assertSessionHasNoErrors();

    $this->post(route('membership.store'), mnForm(['email' => 'andere@example.org']))->assertSessionHasErrors('last_name');
    $this->post(route('membership.store'), mnForm(['first_name' => 'Anna', 'last_name' => 'Anders', 'birth_date' => '1990-01-01']))->assertSessionHasErrors('last_name');

    expect(Membership::query()->count())->toBe(1);
});

it('lets the same person be recorded again once the old membership has ended', function () {
    Membership::factory()->manual()->ended()->create(['first_name' => 'Erika', 'last_name' => 'Musterfrau', 'birth_date' => '1980-05-17', 'email' => 'erika@example.org']);

    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm())->assertSessionHasNoErrors();

    expect(Membership::query()->count())->toBe(2);
});

// ---------- Dokumente ----------

it('stores the document privately under a random name and keeps the original name', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm(['document' => mnPdf('Antrag Müller (final).pdf')]));

    $document = MembershipDocument::query()->firstOrFail();

    expect($document->original_name)->toBe('Antrag Müller (final).pdf')
        ->and($document->kind)->toBe('application')
        ->and($document->mime)->toBe('application/pdf')
        ->and($document->path)->toStartWith('membership-documents/'.$document->membership_id.'/')
        ->and($document->path)->toEndWith('.pdf')
        ->and($document->path)->not->toContain('Antrag')
        ->and($document->sha256)->toHaveLength(64)
        ->and($document->uploaded_by_user_id)->not->toBeNull();

    Storage::disk('local')->assertExists($document->path);
});

it('delivers a document as a private download', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $document = MembershipDocument::query()->firstOrFail();

    $response = $this->get(route('membership.documents.download', [$document->membership_id, $document]));

    $response->assertOk()->assertDownload('antrag.pdf');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('forbids documents to everyone but the board', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $document = MembershipDocument::query()->firstOrFail();
    $url = route('membership.documents.download', [$document->membership_id, $document]);

    foreach ([RoleKey::Administration, RoleKey::AdministrationStaff, RoleKey::Member] as $role) {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

        $this->actingAs($user)->get($url)->assertForbidden();
        $this->post(route('membership.documents.store', $document->membership_id), ['kind' => 'record', 'document' => mnPdf()])->assertForbidden();
        $this->delete(route('membership.documents.destroy', [$document->membership_id, $document]))->assertForbidden();
    }
});

it('sends guests to the login for documents', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $document = MembershipDocument::query()->firstOrFail();

    $this->app['auth']->guard('web')->logout();
    $this->flushSession();

    $this->get(route('membership.documents.download', [$document->membership_id, $document]))->assertRedirect(route('login'));
});

it('does not deliver a document through another membership', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $document = MembershipDocument::query()->firstOrFail();
    $other = Membership::factory()->manual()->create();

    $this->get(route('membership.documents.download', [$other, $document]))->assertNotFound();
    $this->delete(route('membership.documents.destroy', [$other, $document]))->assertNotFound();
    Storage::disk('local')->assertExists($document->path);
});

it('adds more documents and removes them with a note in the history', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $membership = Membership::query()->firstOrFail();

    $this->post(route('membership.documents.store', $membership), ['kind' => 'record', 'document' => mnPdf('nachweis.pdf')])->assertSessionHas('success');
    expect($membership->documents()->count())->toBe(2);

    $document = $membership->documents()->where('kind', 'record')->firstOrFail();
    $path = $document->path;

    $this->delete(route('membership.documents.destroy', [$membership, $document]))->assertSessionHas('success');

    Storage::disk('local')->assertMissing($path);
    expect($membership->documents()->count())->toBe(1)
        ->and($membership->events()->pluck('type')->map->value->all())->toContain('document_added', 'document_removed');
});

it('accepts only pdf, jpeg and png up to ten megabytes', function () {
    $this->actingAs(mnBoard());

    $this->post(route('membership.store'), mnForm(['document' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]))->assertSessionHasErrors('document');
    $this->post(route('membership.store'), mnForm(['document' => UploadedFile::fake()->create('text.txt', 10, 'text/plain')]))->assertSessionHasErrors('document');
    $this->post(route('membership.store'), mnForm(['document' => UploadedFile::fake()->create('zu-gross.pdf', 10241, 'application/pdf')]))->assertSessionHasErrors('document');
    expect(Membership::query()->count())->toBe(0);

    $this->post(route('membership.store'), mnForm(['last_name' => 'Bild', 'email' => 'bild@example.org', 'document' => UploadedFile::fake()->create('scan.jpg', 100, 'image/jpeg')]))->assertSessionHasNoErrors();
    $this->post(route('membership.store'), mnForm(['last_name' => 'Png', 'email' => 'png@example.org', 'document' => UploadedFile::fake()->create('scan.png', 100, 'image/png')]))->assertSessionHasNoErrors();
    expect(Membership::query()->count())->toBe(2);
});

// ---------- Anzeige ----------

it('lists and finds memberships without a person and marks them', function () {
    Membership::factory()->manual()->create(['first_name' => 'Papier', 'last_name' => 'Mitglied']);
    Membership::factory()->create(['person_id' => Person::factory()->create(['first_name' => 'Online', 'last_name' => 'Mitglied'])]);

    $this->actingAs(mnBoard());

    $this->get(route('membership.index'))->assertOk()->assertSee('Papier Mitglied')->assertSee('Online Mitglied')->assertSee('Kein Konto');
    $this->get(route('membership.index', ['q' => 'Papier']))->assertSee('Papier Mitglied')->assertDontSee('Online Mitglied');
    $this->get(route('membership.index', ['sort' => 'name', 'direction' => 'desc']))->assertOk();
    $this->get(route('membership.index', ['sort' => 'requested_on']))->assertOk();
});

it('shows a membership without a person with its documents and actions', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $membership = Membership::query()->firstOrFail();

    $this->get(route('membership.show', $membership))
        ->assertOk()
        ->assertSee('Erika Musterfrau')
        ->assertSee('erika@example.org')
        ->assertSee('Vom Vorstand erfasst')
        ->assertSee('noch keins')
        ->assertSee('antrag.pdf')
        ->assertSee('data-vdbs-dialog-open="admit"', false)
        ->assertSee('data-vdbs-dialog-open="registration-link"', false);
});

it('admits and terminates a membership without a person without trouble', function () {
    $this->actingAs(mnBoard())->post(route('membership.store'), mnForm());
    $membership = Membership::query()->firstOrFail();

    $this->post(route('membership.admit', $membership), ['starts_on' => '2025-02-01'])->assertSessionHas('success');
    $this->post(route('membership.terminate', $membership), ['ends_on' => now()->addMonth()->toDateString()])->assertSessionHas('success');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Active)->and(RoleAssignment::query()->where('source_type', 'membership')->count())->toBe(0);

    $this->artisan('membership:sync')->assertSuccessful();
});
