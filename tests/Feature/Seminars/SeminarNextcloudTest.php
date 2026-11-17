<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Jobs\SyncSeminarFolder;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Nextcloud\FakeNextcloudClient;
use App\Modules\Seminars\Nextcloud\NextcloudClient;
use App\Modules\Seminars\Nextcloud\SeminarFolders;
use App\Modules\Seminars\Nextcloud\SeminarNextcloud;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function ncsUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function ncsFake(): FakeNextcloudClient
{
    $fake = new FakeNextcloudClient;
    app()->instance(NextcloudClient::class, $fake);

    return $fake;
}

function ncsSeminar(array $attributes = [], string $date = '2026-09-15'): Seminar
{
    $partner = Partner::factory()->create(['name' => 'Schule: Nord/Ost', 'coordination_area' => 'Neukölln']);
    $seminar = Seminar::factory()->create($attributes + ['partner_id' => $partner->id, 'number' => 'S-2026-0007', 'title' => 'SV-Tage', 'status' => SeminarStatus::TeamConfirmed]);
    $seminar->sessions()->create(['starts_at' => $date.' 09:00', 'ends_at' => $date.' 15:00']);

    return $seminar->fresh();
}

it('builds the folder path from area, school, school year and seminar with clean names', function () {
    $autumn = ncsSeminar([], '2026-09-15');
    $spring = ncsSeminar([], '2027-03-02');

    expect(SeminarFolders::root($autumn))->toBe('Koordination/Neukölln/Schule Nord Ost/2026-27/S-2026-0007 SV-Tage')
        ->and(SeminarFolders::root($spring))->toContain('/2026-27/')
        ->and(SeminarFolders::clean('  a/b:c?  ', 'x'))->toBe('a b c')
        ->and(SeminarFolders::clean('///', 'Fallback'))->toBe('Fallback');
});

it('creates team and result folders, a folder per day for series, and gives only the confirmed team access', function () {
    $fake = ncsFake();
    $seminar = ncsSeminar();
    $seminar->sessions()->create(['starts_at' => '2026-09-22 09:00', 'ends_at' => '2026-09-22 15:00']);
    $member = ncsUser(RoleKey::Team);
    $applied = ncsUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $member->id, 'status' => ApplicationStatus::Confirmed]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $applied->id, 'status' => ApplicationStatus::Applied]);

    app(SeminarNextcloud::class)->provision($seminar->fresh());

    $root = SeminarFolders::root($seminar);
    expect($fake->folders)->toContain($root.'/Unterlagen (Team)')->toContain($root.'/Ergebnisse (Schule)')->toContain($root.'/Unterlagen (Team)/Tag 2 – 22.09.2026')
        ->and($fake->access[$root])->toBe([(string) $member->public_uuid])
        ->and($seminar->fresh()->nextcloud_status)->toBe('ok')->and($seminar->fresh()->nextcloud_folder_path)->toBe($root);
});

it('records a failure on the seminar and in the history without losing the error', function () {
    $fake = ncsFake();
    $fake->fail = true;
    $seminar = ncsSeminar();

    expect(fn () => app(SeminarNextcloud::class)->provision($seminar))->toThrow(RuntimeException::class);

    expect($seminar->fresh()->nextcloud_status)->toBe('failed')->and($seminar->fresh()->nextcloud_error)->toContain('nicht erreichbar')
        ->and($seminar->events()->where('comment', 'like', 'Nextcloud-Ordner konnten nicht%')->exists())->toBeTrue();
});

it('creates the result link for two years, refuses a shorter one and passes a longer expiry on to Nextcloud', function () {
    $fake = ncsFake();
    $seminar = ncsSeminar();
    $service = app(SeminarNextcloud::class);

    expect(fn () => $service->createResultsLink($seminar, null))->toThrow(DomainException::class, 'Ordner');

    $service->provision($seminar);
    $seminar = $seminar->fresh();
    expect(fn () => $service->createResultsLink($seminar, '2027-01-01'))->toThrow(DomainException::class, 'ein Jahr');

    $result = $service->createResultsLink($seminar, null);
    expect($result['expires'])->toBe('2028-09-15')->and($seminar->fresh()->results_share_url)->toBe($result['url'])->and($fake->shares[$seminar->fresh()->results_share_id]['path'])->toEndWith('Ergebnisse (Schule)');

    $coordination = ncsUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('seminars.report.extend', $seminar), ['results_expires_at' => '2029-01-01'])->assertSessionHas('success');
    expect($fake->shares[$seminar->fresh()->results_share_id]['expires'])->toBe('2029-01-01');
});

it('starts the folder job with the team confirmation only when Nextcloud is set up', function () {
    Queue::fake();
    $seminar = ncsSeminar(['status' => SeminarStatus::TeamSelected]);
    app(SeminarWorkflow::class)->change($seminar, SeminarStatus::TeamConfirmed, ncsUser(RoleKey::Coordination));
    Queue::assertNothingPushed();

    ncsFake();
    $other = ncsSeminar(['status' => SeminarStatus::TeamSelected]);
    app(SeminarWorkflow::class)->change($other, SeminarStatus::TeamConfirmed, ncsUser(RoleKey::Coordination));
    Queue::assertPushed(SyncSeminarFolder::class);
});

it('wants the folder before the seminar counts as prepared, or a note that it is done by hand', function () {
    ncsFake();
    $seminar = ncsSeminar(['onboarding_at' => now()->addDays(3)]);
    $workflow = app(SeminarWorkflow::class);
    $coordination = ncsUser(RoleKey::Coordination);

    expect(fn () => $workflow->change($seminar, SeminarStatus::Prepared, $coordination))->toThrow(DomainException::class, 'Nextcloud');

    $this->actingAs($coordination)->post(route('seminars.nextcloud.manual', $seminar))->assertSessionHas('success');
    $workflow->change($seminar->fresh(), SeminarStatus::Prepared, $coordination);

    expect($seminar->fresh()->status)->toBe(SeminarStatus::Prepared);
});

it('lets the coordination create the folders from the seminar and explains a missing setup', function () {
    $seminar = ncsSeminar();
    $coordination = ncsUser(RoleKey::Coordination);

    $this->actingAs(ncsUser(RoleKey::Team))->post(route('seminars.nextcloud.sync', $seminar))->assertForbidden();
    $this->actingAs($coordination)->post(route('seminars.nextcloud.sync', $seminar))->assertSessionHas('warning');

    ncsFake();
    $this->actingAs($coordination)->post(route('seminars.nextcloud.sync', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->nextcloud_folder_path)->not->toBeNull();

    $this->actingAs($coordination)->post(route('seminars.nextcloud.link', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->results_share_url)->toStartWith('https://cloud.example.org/s/');
});

it('creates the result link in Nextcloud when the report goes to the school without a typed link', function () {
    ncsFake();
    $seminar = ncsSeminar(['status' => SeminarStatus::Reported]);
    app(SeminarNextcloud::class)->provision($seminar);
    $coordination = ncsUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('seminars.report.send', $seminar), [])->assertSessionHas('success');

    expect($seminar->fresh()->results_share_url)->toStartWith('https://cloud.example.org/s/')->and($seminar->fresh()->report_sent_at)->not->toBeNull();
});
