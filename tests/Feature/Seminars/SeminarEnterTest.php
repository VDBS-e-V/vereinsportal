<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function enUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function enSent(string $to, string $subjectStart): bool
{
    return Mail::sent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($to) && str_starts_with($mail->subjectLine, $subjectStart))->isNotEmpty();
}

/** @return array<string, mixed> */
function enForm(Partner $partner, array $extra = []): array
{
    return array_merge(['partner_id' => $partner->id, 'title' => 'Medientag', 'format' => 'Projekttag', 'participants' => 40, 'contact_name' => 'Frau Lehrkraft', 'contact_email' => 'lehrkraft@schule.example'], $extra);
}

it('lets coordination, office and board enter a request, but nobody else', function () {
    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(enUser($role))->get(route('seminars.enter.create'))->assertOk()->assertSee('Anfrage eintragen');
    }

    foreach ([RoleKey::Team, RoleKey::Member, null] as $role) {
        $this->actingAs(enUser($role))->get(route('seminars.enter.create'))->assertForbidden();
        $this->post(route('seminars.enter.store'), enForm(Partner::factory()->create()))->assertForbidden();
    }

    auth()->logout();
    $this->get(route('seminars.enter.create'))->assertRedirect();
});

it('creates the request for the school without confirming it to the school', function () {
    $office = enUser(RoleKey::AdministrationStaff);
    $coordination = Coordination::query()->create(['name' => 'Mitte']);
    $partner = Partner::factory()->create(['coordination_id' => $coordination->id]);
    $author = enUser(RoleKey::Coordination);

    $this->actingAs($author)->post(route('seminars.enter.store'), enForm($partner))->assertRedirect(route('seminars.plan.index'))->assertSessionHas('success');

    $seminar = Seminar::query()->firstOrFail();

    expect($seminar->status)->toBe(SeminarStatus::Request)
        ->and($seminar->partner_id)->toBe($partner->id)
        ->and($seminar->coordination_id)->toBe($coordination->id)
        ->and($seminar->requested_by_user_id)->toBe($author->id)
        ->and($seminar->number)->not->toBeEmpty()
        ->and($seminar->events()->where('type', 'requested')->value('comment'))->toContain('Im Auftrag')
        ->and(enSent('lehrkraft@schule.example', 'Ihre Seminaranfrage'))->toBeFalse()
        ->and(enSent($office->email, 'Neue Seminaranfrage'))->toBeTrue();

    $this->assertDatabaseHas('audit_entries', ['event' => 'seminars.requested']);
});

it('sends the office to the new request after it entered one itself', function () {
    $partner = Partner::factory()->create();

    $this->actingAs(enUser(RoleKey::AdministrationStaff))->post(route('seminars.enter.store'), enForm($partner));

    $seminar = Seminar::query()->firstOrFail();

    $this->get(route('seminars.office.show', $seminar))->assertOk()->assertSee('Medientag');
});

it('takes the contact of the school when none is given and complains when there is none', function () {
    $author = enUser(RoleKey::AdministrationStaff);
    $partner = Partner::factory()->create();

    $this->actingAs($author)->post(route('seminars.enter.store'), enForm($partner, ['contact_name' => '', 'contact_email' => '']))->assertSessionHasErrors('contact_email');
    expect(Seminar::query()->count())->toBe(0);

    PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Herr Schulleiter', 'email' => 'Leiter@Schule.example', 'is_primary' => true]);

    $this->post(route('seminars.enter.store'), enForm($partner, ['contact_name' => '', 'contact_email' => '']))->assertSessionHasNoErrors();

    $seminar = Seminar::query()->firstOrFail();

    expect($seminar->contact_name)->toBe('Herr Schulleiter')->and($seminar->contact_email)->toBe('leiter@schule.example');
});

it('validates school and title', function () {
    $this->actingAs(enUser(RoleKey::Coordination));

    $this->post(route('seminars.enter.store'), enForm(Partner::factory()->create(), ['partner_id' => '', 'title' => '']))->assertSessionHasErrors(['partner_id', 'title']);
    $this->post(route('seminars.enter.store'), enForm(Partner::factory()->create(), ['partner_id' => 99999]))->assertSessionHasErrors('partner_id');

    expect(Seminar::query()->count())->toBe(0);
});

it('shows coordination the requests under review, read-only', function () {
    $partner = Partner::factory()->create(['name' => 'Schule Beispiel']);
    $seminar = Seminar::factory()->status(SeminarStatus::InReview)->create(['partner_id' => $partner->id, 'title' => 'Datenschutztag']);

    $this->actingAs(enUser(RoleKey::Coordination))->get(route('seminars.plan.index'))
        ->assertOk()->assertSee('Anfragen in Prüfung')->assertSee('Datenschutztag')->assertDontSee(route('seminars.plan.show', $seminar));
});
