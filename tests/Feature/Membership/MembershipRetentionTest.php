<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipConsent;
use App\Modules\Membership\Models\MembershipDocument;
use App\Modules\People\Models\Person;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    config(['portal.membership.retention' => ['unverified_days' => 7, 'rejected_days' => 180, 'ended_days' => null]]);
});

/** Online-Antrag mit Konto, so alt wie angegeben, Konto unbestätigt oder bestätigt. */
function rtApplication(int $ageDays, bool $verified = false): Membership
{
    $person = Person::factory()->create();
    User::factory()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'status' => $verified ? UserStatus::Active : UserStatus::PendingVerification,
        'email_verified_at' => $verified ? now() : null,
    ]);

    $membership = Membership::factory()->requested()->create(['person_id' => $person->id, 'source' => 'online']);
    $membership->forceFill(['created_at' => now()->subDays($ageDays)])->save();
    $membership->consents()->create(['type' => 'privacy', 'version' => '2026-01', 'accepted_at' => now()]);

    return $membership;
}

function rtDocument(Membership $membership): MembershipDocument
{
    $path = 'membership-documents/'.$membership->id.'/datei.pdf';
    Storage::disk('local')->put($path, 'pdf');

    return $membership->documents()->create(['kind' => 'application', 'original_name' => 'a.pdf', 'path' => $path, 'mime' => 'application/pdf', 'size' => 3, 'sha256' => str_repeat('a', 64), 'uploaded_at' => now()]);
}

// ---------- Unbestätigte Online-Anträge ----------

it('deletes unconfirmed online applications with account and person after the period', function () {
    $old = rtApplication(8);
    $young = rtApplication(3);
    $verified = rtApplication(30, true);
    $oldPerson = $old->person;
    $oldUser = $oldPerson->user;

    $this->artisan('membership:prune')->assertSuccessful();

    expect(Membership::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(Person::query()->whereKey($oldPerson->id)->exists())->toBeFalse()
        ->and(User::query()->whereKey($oldUser->id)->exists())->toBeFalse()
        ->and(MembershipConsent::query()->where('membership_id', $old->id)->exists())->toBeFalse()
        ->and(Membership::query()->whereKey($young->id)->exists())->toBeTrue()
        ->and(Membership::query()->whereKey($verified->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($verified->person->user->id)->exists())->toBeTrue();
});

it('never touches invited accounts, paper applications or accounts that logged in', function () {
    $invited = User::factory()->create(['status' => UserStatus::PendingVerification, 'created_at' => now()->subDays(30)]);
    $paper = Membership::factory()->manual()->requested()->create(['created_at' => now()->subDays(30)]);
    $loggedIn = rtApplication(30);
    $loggedIn->person->user->forceFill(['last_login_at' => now()->subDay()])->save();

    $this->artisan('membership:prune')->assertSuccessful();

    expect(User::query()->whereKey($invited->id)->exists())->toBeTrue()
        ->and(Membership::query()->whereKey($paper->id)->exists())->toBeTrue()
        ->and(Membership::query()->whereKey($loggedIn->id)->exists())->toBeTrue();
});

// ---------- Abgelehnte Anträge ----------

it('deletes rejected applications with files after the period but keeps the account of the person', function () {
    $person = Person::factory()->create();
    $account = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    $old = Membership::factory()->rejected()->create(['person_id' => $person->id, 'decided_at' => now()->subDays(200)]);
    $document = rtDocument($old);
    $recent = Membership::factory()->rejected()->create(['decided_at' => now()->subDays(20)]);

    $this->artisan('membership:prune')->assertSuccessful();

    expect(Membership::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(MembershipDocument::query()->whereKey($document->id)->exists())->toBeFalse()
        ->and(Membership::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($account->id)->exists())->toBeTrue()
        ->and(Person::query()->whereKey($person->id)->exists())->toBeTrue();

    Storage::disk('local')->assertMissing($document->path);
});

// ---------- Beendete Mitgliedschaften ----------

it('keeps ended memberships unless a period is configured', function () {
    $ended = Membership::factory()->ended()->create(['ends_on' => now()->subYears(20)->toDateString(), 'starts_on' => now()->subYears(25)->toDateString()]);

    $this->artisan('membership:prune')->assertSuccessful();

    expect(Membership::query()->whereKey($ended->id)->exists())->toBeTrue();
});

it('deletes ended memberships after a configured period and leaves running ones', function () {
    config(['portal.membership.retention.ended_days' => 3650]);
    $old = Membership::factory()->ended()->create(['starts_on' => now()->subYears(25)->toDateString(), 'ends_on' => now()->subYears(11)->toDateString()]);
    $lapsed = Membership::factory()->create(['starts_on' => now()->subYears(25)->toDateString(), 'ends_on' => now()->subYears(12)->toDateString()]);
    $recent = Membership::factory()->ended()->create();
    $running = Membership::factory()->create();
    rtDocument($old);

    $this->artisan('membership:prune')->assertSuccessful();

    expect(Membership::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(Membership::query()->whereKey($lapsed->id)->exists())->toBeFalse()
        ->and(Membership::query()->whereKey($recent->id)->exists())->toBeTrue()
        ->and(Membership::query()->whereKey($running->id)->exists())->toBeTrue()
        ->and($running->fresh()->status)->toBe(MembershipStatus::Active);

    Storage::disk('local')->assertMissing('membership-documents/'.$old->id.'/datei.pdf');
});

// ---------- Trockenlauf und Plan ----------

it('only counts in a dry run', function () {
    $old = rtApplication(30);
    $rejected = Membership::factory()->rejected()->create(['decided_at' => now()->subDays(400)]);

    $this->artisan('membership:prune', ['--dry-run' => true])->expectsOutputToContain('Trockenlauf')->expectsOutputToContain('abgelehnte: 1, beendete: 0')->assertSuccessful();

    expect(Membership::query()->whereKey($old->id)->exists())->toBeTrue()->and(Membership::query()->whereKey($rejected->id)->exists())->toBeTrue();
});

it('switches single periods off with an empty or zero value', function () {
    config(['portal.membership.retention' => ['unverified_days' => 0, 'rejected_days' => null, 'ended_days' => '']]);
    $old = rtApplication(100);
    $rejected = Membership::factory()->rejected()->create(['decided_at' => now()->subDays(900)]);

    $this->artisan('membership:prune')->assertSuccessful();

    expect(Membership::query()->count())->toBe(2)->and($rejected->fresh())->not->toBeNull()->and($old->fresh())->not->toBeNull();
});

it('is scheduled daily', function () {
    $schedule = file_get_contents(base_path('routes/console.php'));

    expect($schedule)->toContain("Schedule::command('membership:prune')->daily()")->toContain("Schedule::command('membership:sync')->daily()");
});
