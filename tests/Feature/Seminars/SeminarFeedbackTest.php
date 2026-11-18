<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarFeedback;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function fbUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Seminar mit gesendetem Bericht und Schulkonto. */
function fbSeminar(bool $sent = true, ?Coordination $coordination = null): array
{
    $partner = Partner::factory()->create();
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => fake()->unique()->safeEmail()]);
    $account = app(SchoolAccounts::class)->invite($partner, $contact, fbUser(RoleKey::Coordination));
    $account->user->forceFill(['status' => UserStatus::Active])->save();
    $seminar = Seminar::factory()->status(SeminarStatus::Reported)->create(['partner_id' => $partner->id, 'coordination_id' => $coordination?->id, 'school_year' => '2026/27', 'report_sent_at' => $sent ? now() : null]);

    return [$seminar, $account->user->fresh()];
}

function fbRatings(int $value = 5): array
{
    return ['organization' => $value, 'team' => $value, 'content' => $value, 'overall' => $value];
}

it('lets the school give feedback once after it received the report', function () {
    [$seminar, $school] = fbSeminar();
    $this->actingAs($school);

    $this->get(route('school.seminars.show', $seminar))->assertOk()->assertSee('Ihre Rückmeldung')->assertSee('Rückmeldung senden');

    $this->post(route('school.seminars.feedback', $seminar), ['ratings' => ['organization' => 5]])->assertSessionHasErrors(['ratings.team', 'ratings.content', 'ratings.overall']);
    $this->post(route('school.seminars.feedback', $seminar), ['ratings' => fbRatings(6)])->assertSessionHasErrors('ratings.organization');

    $this->post(route('school.seminars.feedback', $seminar), ['ratings' => fbRatings(4), 'comment' => ' Hat gut geklappt '])->assertSessionHas('success');

    $feedback = SeminarFeedback::query()->firstOrFail();

    expect($feedback->rating('team'))->toBe(4)->and($feedback->comment)->toBe('Hat gut geklappt')->and($seminar->events()->where('type', 'feedback')->count())->toBe(1);

    $this->post(route('school.seminars.feedback', $seminar), ['ratings' => fbRatings(1)])->assertSessionHasErrors('feedback');
    expect(SeminarFeedback::query()->count())->toBe(1);
    $this->get(route('school.seminars.show', $seminar))->assertSee('Rückmeldung ist am');
});

it('refuses feedback before the report was sent and from other schools', function () {
    [$early, $school] = fbSeminar(false);
    [, $foreign] = fbSeminar();

    $this->actingAs($school)->post(route('school.seminars.feedback', $early), ['ratings' => fbRatings()])->assertSessionHasErrors('feedback');
    $this->actingAs($foreign)->post(route('school.seminars.feedback', $early), ['ratings' => fbRatings()])->assertNotFound();

    expect(SeminarFeedback::query()->count())->toBe(0);
});

it('shows the coordination the averages and the filtered feedback', function () {
    $mitte = Coordination::query()->create(['name' => 'Mitte']);
    [$one, $schoolOne] = fbSeminar(true, $mitte);
    [$two, $schoolTwo] = fbSeminar(true);
    $this->actingAs($schoolOne)->post(route('school.seminars.feedback', $one), ['ratings' => fbRatings(5), 'comment' => 'Sehr gut']);
    $this->actingAs($schoolTwo)->post(route('school.seminars.feedback', $two), ['ratings' => fbRatings(3), 'comment' => 'Geht so']);

    $this->actingAs(fbUser(RoleKey::AdministrationStaff))->get(route('seminars.plan.feedback'))->assertForbidden();

    $this->actingAs(fbUser(RoleKey::Coordination));
    $this->get(route('seminars.plan.feedback'))->assertOk()->assertSee('Sehr gut')->assertSee('Geht so')->assertSee('4,0');
    $this->get(route('seminars.plan.feedback', ['coordination' => $mitte->id]))->assertOk()->assertSee('Sehr gut')->assertDontSee('Geht so')->assertSee('5,0');
});
