<?php

use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\Access\Models\User;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarNumbers;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

afterEach(fn () => Carbon::setTestNow());

function snCoordination(): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create([
        'user_id' => $user->id,
        'role_id' => Role::query()->where('key', RoleKey::Coordination->value)->value('id'),
        'source' => RoleAssignmentSource::Manual,
        'starts_at' => now()->subMinute(),
    ]);

    return $user;
}

it('numbers the first seminar of a year 0001 and counts up without gaps', function () {
    Carbon::setTestNow('2027-03-10 10:00:00');

    $numbers = collect(range(1, 3))->map(fn () => Seminar::factory()->create()->number)->all();

    expect($numbers)->toBe(['S-2027-0001', 'S-2027-0002', 'S-2027-0003']);
});

it('starts again at one in a new year', function () {
    Carbon::setTestNow('2027-12-31 23:00:00');
    Seminar::factory()->create();
    Carbon::setTestNow('2028-01-01 08:00:00');

    expect(Seminar::factory()->create()->number)->toBe('S-2028-0001');
});

it('never gives out a number twice, not even after deleting', function () {
    Carbon::setTestNow('2027-03-10 10:00:00');
    $first = Seminar::factory()->create();
    $first->delete();

    expect(Seminar::factory()->create()->number)->toBe('S-2027-0002');
});

it('hands out different numbers for the same moment', function () {
    $at = Carbon::parse('2027-05-05 12:00:00');
    $service = app(SeminarNumbers::class);

    expect($service->next($at))->not->toBe($service->next($at));
});

it('never leaves a seminar without a number, also when it is created in another way', function () {
    $seminar = Seminar::query()->create(['title' => 'Test', 'type' => 'sv_days', 'partner_id' => Partner::factory()->create()->id]);

    expect($seminar->number)->toMatch('/^S-\d{4}-\d{4}$/');
});

it('keeps the number when the seminar is changed', function () {
    $seminar = Seminar::factory()->create();
    $number = $seminar->number;

    $seminar->forceFill(['title' => 'Neuer Titel', 'number' => 'S-1999-9999'])->save();

    expect($seminar->fresh()->number)->toBe($number)->and($seminar->fresh()->title)->toBe('Neuer Titel');
});

it('numbers old seminars by creation date and leaves existing numbers alone', function () {
    $old = Seminar::factory()->create();
    $newer = Seminar::factory()->create();
    $kept = Seminar::factory()->create();
    DB::table('seminars')->where('id', $old->id)->update(['number' => null, 'created_at' => '2026-02-01 10:00:00']);
    DB::table('seminars')->where('id', $newer->id)->update(['number' => null, 'created_at' => '2026-06-01 10:00:00']);
    DB::table('seminar_counters')->delete();
    $keptNumber = $kept->number;

    $this->artisan('seminars:number-existing')->assertSuccessful();

    expect($old->fresh()->number)->toBe('S-2026-0001')->and($newer->fresh()->number)->toBe('S-2026-0002')->and($kept->fresh()->number)->toBe($keptNumber);

    $this->artisan('seminars:number-existing')->assertSuccessful();
    expect($old->fresh()->number)->toBe('S-2026-0001');
});

it('uses the configured format', function () {
    config(['portal.seminars.number_format' => 'SVT-{Y}-{N:3}']);

    expect(app(SeminarNumbers::class)->format(2027, 42))->toBe('SVT-2027-042');
});

it('finds seminars by the full number, a part of it and the running number', function () {
    $user = snCoordination();
    $target = Seminar::factory()->create(['title' => 'Gesuchtes Seminar']);
    DB::table('seminars')->where('id', $target->id)->update(['number' => 'S-2027-0042']);
    Seminar::factory()->create(['title' => 'Anderes Seminar']);

    foreach (['S-2027-0042', '2027-0042', '0042'] as $search) {
        $this->actingAs($user)->get(route('seminars.index', ['q' => $search]))->assertOk()->assertSee('Gesuchtes Seminar')->assertDontSee('Anderes Seminar');
    }
});

it('shows the number in the list, the heading and the quote', function () {
    $user = snCoordination();
    $seminar = Seminar::factory()->create();

    $this->actingAs($user)->get(route('seminars.index'))->assertOk()->assertSee($seminar->number);
    $this->actingAs($user)->get(route('seminars.show', $seminar))->assertOk()->assertSee($seminar->number);
});

it('saves the master data and rejects a foreign coordinator', function () {
    $user = snCoordination();
    $partner = Partner::factory()->create();
    $outsider = User::factory()->create();

    $data = ['title' => 'SV-Tage', 'type' => 'sv_days', 'partner_id' => $partner->id, 'classes_count' => '3', 'location_notes' => 'Raum 12, Hausmeister anrufen', 'cost_center' => 'K-100', 'coordinator_user_id' => $user->id];

    $this->actingAs($user)->post(route('seminars.store'), $data)->assertSessionHasNoErrors();
    $seminar = Seminar::query()->latest('id')->first();
    expect($seminar->classes_count)->toBe(3)->and($seminar->cost_center)->toBe('K-100')->and($seminar->coordinator_user_id)->toBe($user->id);

    $this->post(route('seminars.store'), array_merge($data, ['coordinator_user_id' => $outsider->id]))->assertSessionHasErrors('coordinator_user_id');
});

it('offers the placeholder for the seminar number in team letters', function () {
    $values = app(\App\Modules\Mailings\Services\Mailings::class)->seminarValues(Seminar::factory()->create());

    expect($values['seminar_nr'])->toMatch('/^S-\d{4}-\d{4}$/')
        ->and(\App\Modules\Mailings\Support\MailPlaceholders::definitions('team'))->toHaveKey('seminar_nr');
});

it('gives school requests a number and offers the number task to the administration', function () {
    $seminar = app(SeminarAdministration::class)->create(['title' => 'Anfrage', 'type' => 'sv_days', 'partner_id' => Partner::factory()->create()->id], snCoordination());

    expect($seminar->number)->not->toBeNull()
        ->and(app(\App\Modules\System\Services\SystemTasks::class)->find('seminarnummern-nachtragen')['command'])->toBe('seminars:number-existing');
});
