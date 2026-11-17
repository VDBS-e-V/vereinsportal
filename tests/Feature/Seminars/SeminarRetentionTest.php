<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
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

function rtUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function rtOld(SeminarStatus $status, array $attributes = []): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => $status]);
    Seminar::query()->whereKey($seminar->id)->update(['updated_at' => now()->subYears(2)]);

    return $seminar->fresh();
}

it('deletes rejected and cancelled requests without a contract after a year and keeps everything else', function () {
    $rejected = rtOld(SeminarStatus::Rejected);
    $cancelled = rtOld(SeminarStatus::Cancelled);
    $signed = rtOld(SeminarStatus::Cancelled, ['school_contract_signed_at' => now()->subYears(2)->toDateString()]);
    $fresh = Seminar::factory()->create(['status' => SeminarStatus::Rejected]);
    $running = rtOld(SeminarStatus::Held);
    $withInvoice = rtOld(SeminarStatus::Cancelled);
    $withInvoice->documents()->create(['kind' => 'invoice', 'title' => 'Rechnung', 'original_name' => 'r.pdf', 'path' => 'seminars/x/r.pdf', 'mime' => 'application/pdf', 'size' => 10, 'retention_until' => now()->addYears(10)->toDateString(), 'uploaded_by_user_id' => rtUser(RoleKey::AdministrationStaff)->id]);

    $this->artisan('seminars:prune-requests', ['--dry-run' => true])->assertSuccessful();
    expect(Seminar::query()->count())->toBe(6);

    $this->artisan('seminars:prune-requests')->assertSuccessful();

    expect(Seminar::query()->whereKey($rejected->id)->exists())->toBeFalse()
        ->and(Seminar::query()->whereKey($cancelled->id)->exists())->toBeFalse()
        ->and(Seminar::query()->whereKey($signed->id)->exists())->toBeTrue()
        ->and(Seminar::query()->whereKey($fresh->id)->exists())->toBeTrue()
        ->and(Seminar::query()->whereKey($running->id)->exists())->toBeTrue()
        ->and(Seminar::query()->whereKey($withInvoice->id)->exists())->toBeTrue();
});

it('tells the coordination once before a result link expires and never deletes anything itself', function () {
    $coordination = rtUser(RoleKey::Coordination);
    $soon = Seminar::factory()->create(['status' => SeminarStatus::Completed, 'results_share_url' => 'https://cloud.example.org/s/a', 'results_expires_at' => now()->addDays(10)->toDateString()]);
    $later = Seminar::factory()->create(['status' => SeminarStatus::Completed, 'results_share_url' => 'https://cloud.example.org/s/b', 'results_expires_at' => now()->addYear()->toDateString()]);

    $this->artisan('seminars:results-expiry')->assertSuccessful();

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'results_expiring' && $mail->hasTo($coordination->email) && $mail->seminar->is($soon));
    Mail::assertNotSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->seminar->is($later));
    expect($soon->fresh()->results_expiry_notified_at)->not->toBeNull()->and(Seminar::query()->count())->toBe(2);

    Mail::fake();
    $this->artisan('seminars:results-expiry')->assertSuccessful();
    Mail::assertNothingSent();
});

it('extends a result link but never shortens it, and defaults to the retention of two years', function () {
    $coordination = rtUser(RoleKey::Coordination);
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Reported]);
    $seminar->sessions()->create(['starts_at' => now()->subDays(20), 'ends_at' => now()->subDays(20)->addHours(5)]);

    $this->actingAs($coordination)->post(route('seminars.report.send', $seminar), ['results_share_url' => 'https://cloud.example.org/s/x'])->assertSessionHas('success');
    $expires = $seminar->fresh()->results_expires_at;
    expect($expires->toDateString())->toBe($seminar->sessions()->first()->ends_at->copy()->startOfDay()->addYears(2)->toDateString());

    $this->actingAs($coordination)->post(route('seminars.report.extend', $seminar), ['results_expires_at' => $expires->copy()->subDay()->toDateString()])->assertSessionHasErrors('results_expires_at');
    $this->actingAs($coordination)->post(route('seminars.report.extend', $seminar), ['results_expires_at' => $expires->copy()->addYear()->toDateString()])->assertSessionHas('success');
    expect($seminar->fresh()->results_expires_at->toDateString())->toBe($expires->copy()->addYear()->toDateString());
});
