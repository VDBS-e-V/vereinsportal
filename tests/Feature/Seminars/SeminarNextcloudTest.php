<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Coordinations\Models\Coordination;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Nextcloud\FakeNextcloudClient;
use App\Modules\Seminars\Nextcloud\NextcloudClient;
use App\Modules\Seminars\Services\SeminarNextcloud;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function nxUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function nxFake(bool $failing = false): FakeNextcloudClient
{
    $fake = new FakeNextcloudClient;
    $fake->failing = $failing;
    app()->instance(NextcloudClient::class, $fake);

    return $fake;
}

function nxSeminar(SeminarStatus $status = SeminarStatus::TeamConfirmed, string $number = 'S-2027-0007'): Seminar
{
    $coordination = Coordination::query()->firstOrCreate(['name' => 'Neukölln']);
    $partner = Partner::factory()->create(['name' => 'Goethe/Schule: Nord', 'coordination_id' => $coordination->id]);

    return Seminar::factory()->status($status)->create(['partner_id' => $partner->id, 'coordination_id' => $coordination->id, 'school_year' => '2026/27', 'title' => 'Demokratie im Alltag', 'number' => $number, 'contact_email' => 'lehrkraft@schule.example']);
}

it('builds a clean folder path from coordination, school, school year and seminar', function () {
    $seminar = nxSeminar();

    expect(app(SeminarNextcloud::class)->path($seminar))->toBe('Koordination/Neukölln/Goethe Schule Nord/2026-27/S-2027-0007 Demokratie im Alltag');
});

it('creates every folder up to the seminar and the results folder, and can do it twice', function () {
    $fake = nxFake();
    $seminar = nxSeminar();

    expect(app(SeminarNextcloud::class)->ensureFolders($seminar))->toBeTrue();

    $seminar->refresh();

    expect($fake->folders)->toBe([
        'Koordination', 'Koordination/Neukölln', 'Koordination/Neukölln/Goethe Schule Nord', 'Koordination/Neukölln/Goethe Schule Nord/2026-27',
        'Koordination/Neukölln/Goethe Schule Nord/2026-27/S-2027-0007 Demokratie im Alltag', 'Koordination/Neukölln/Goethe Schule Nord/2026-27/S-2027-0007 Demokratie im Alltag/Ergebnisse',
    ])->and($seminar->nextcloud_path)->toBe('Koordination/Neukölln/Goethe Schule Nord/2026-27/S-2027-0007 Demokratie im Alltag')->and($seminar->nextcloud_error)->toBeNull();

    expect(app(SeminarNextcloud::class)->ensureFolders($seminar))->toBeTrue();
});

it('notes the error and blocks nothing when the nextcloud is not reachable', function () {
    nxFake(true);
    $seminar = nxSeminar();

    expect(app(SeminarNextcloud::class)->ensureFolders($seminar))->toBeFalse();

    $seminar->refresh();

    expect($seminar->nextcloud_path)->toBeNull()->and($seminar->nextcloud_error)->toContain('nicht erreichbar')->and($seminar->events()->where('type', 'nextcloud_failed')->count())->toBe(1);
});

it('does nothing without credentials', function () {
    $seminar = nxSeminar();

    expect(app(SeminarNextcloud::class)->enabled())->toBeFalse()->and(app(SeminarNextcloud::class)->ensureFolders($seminar))->toBeFalse()->and($seminar->fresh()->nextcloud_error)->toBeNull();
});

it('creates the folder in the background when the team is confirmed', function () {
    $fake = nxFake();
    $seminar = nxSeminar(SeminarStatus::TeamSelected);
    $seminar->forceFill(['team_target' => 1])->save();
    $anna = nxUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $anna->id, 'decision' => 'confirm']);
    $this->actingAs(nxUser(RoleKey::Coordination))->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $anna->id])->assertSessionHas('success');

    expect($seminar->fresh()->nextcloud_path)->not->toBeNull()->and($fake->folders)->not->toBeEmpty();
});

it('needs the folder before preparing when the nextcloud is set up, unless it is noted as manual', function () {
    nxFake();
    $anna = nxUser(RoleKey::Team);
    $seminar = nxSeminar();
    $seminar->forceFill(['lead_user_id' => $anna->id, 'onboarding_at' => now()->addWeek()])->save();
    $this->actingAs(nxUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.prepare', $seminar))->assertSessionHasErrors('team');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::TeamConfirmed);

    $this->post(route('seminars.plan.folder', $seminar), ['mode' => 'create'])->assertSessionHas('success');
    $this->post(route('seminars.plan.prepare', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Prepared);

    $manual = nxSeminar(SeminarStatus::TeamConfirmed, 'S-2027-0008');
    $manual->forceFill(['lead_user_id' => $anna->id, 'onboarding_at' => now()->addWeek()])->save();
    $this->post(route('seminars.plan.folder', $manual), ['mode' => 'manual'])->assertSessionHas('success');
    $this->post(route('seminars.plan.prepare', $manual))->assertSessionHas('success');
    expect($manual->fresh()->nextcloud_manual)->toBeTrue()->and($manual->fresh()->status)->toBe(SeminarStatus::Prepared);
});

it('shares the results folder with an expiry date when sending the results', function () {
    $fake = nxFake();
    $seminar = nxSeminar(SeminarStatus::Reported);
    $seminar->sessions()->create(['starts_at' => now()->subDay()]);
    $coordination = nxUser(RoleKey::Coordination);
    $expires = now()->addYears(2);

    app(SeminarWorkflow::class)->sendResults($seminar, null, $expires, $coordination);

    $seminar->refresh();

    expect($seminar->results_url)->toBe('https://cloud.example/s/fake1')->and($seminar->nextcloud_share_id)->toBe('1')->and($fake->shares['1']['path'])->toEndWith('/Ergebnisse')->and($fake->shares['1']['expires'])->toBe($expires->format('Y-m-d'));

    // Verlängern ändert das Datum auch in der Nextcloud, kürzen geht nicht
    $later = now()->addYears(3);
    app(SeminarWorkflow::class)->extendResults($seminar, $later, $coordination);

    expect($seminar->fresh()->results_expires_on->toDateString())->toBe($later->toDateString())->and($fake->shares['1']['expires'])->toBe($later->format('Y-m-d'));
    expect(fn () => app(SeminarWorkflow::class)->extendResults($seminar->fresh(), now()->addYear(), $coordination))->toThrow(DomainException::class, 'nach dem bisherigen');
});

it('asks for the link when there is no nextcloud and none was given', function () {
    $seminar = nxSeminar(SeminarStatus::Reported);
    $seminar->sessions()->create(['starts_at' => now()->subDay()]);

    expect(fn () => app(SeminarWorkflow::class)->sendResults($seminar, null, now()->addYears(2), nxUser(RoleKey::Coordination)))->toThrow(DomainException::class, 'Link zum Ergebnisordner');
});
