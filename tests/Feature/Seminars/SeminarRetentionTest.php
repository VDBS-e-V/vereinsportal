<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\PaymentOrder;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

function rkUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

it('deletes old rejected and cancelled requests without a contract, with their files, and keeps the rest', function () {
    $old = Seminar::factory()->status(SeminarStatus::Rejected)->create();
    $old->documents()->create(['kind' => 'other', 'title' => 'Notiz', 'path' => 'seminars/'.$old->id.'/n.pdf', 'original_name' => 'n.pdf', 'mime' => 'application/pdf', 'size' => 4]);
    Storage::disk('local')->put('seminars/'.$old->id.'/n.pdf', 'test');
    $cancelled = Seminar::factory()->status(SeminarStatus::Cancelled)->create();
    $young = Seminar::factory()->status(SeminarStatus::Rejected)->create();
    $signed = Seminar::factory()->status(SeminarStatus::Cancelled)->create(['school_contract_signed_on' => now()->subYears(2)]);
    $paid = Seminar::factory()->status(SeminarStatus::Cancelled)->create();
    PaymentOrder::query()->create(['seminar_id' => $paid->id, 'user_id' => rkUser()->id, 'amount_cents' => 1000]);
    $running = Seminar::factory()->status(SeminarStatus::Planned)->create();

    foreach ([$old, $cancelled, $signed, $paid, $running] as $seminar) {
        $seminar->forceFill(['updated_at' => now()->subYears(2)])->saveQuietly();
    }

    $this->artisan('seminars:prune-requests', ['--dry-run' => true])->expectsOutputToContain('Gelöschte Anfragen: 2')->assertSuccessful();
    expect(Seminar::query()->count())->toBe(6);

    $this->artisan('seminars:prune-requests')->expectsOutputToContain('Gelöschte Anfragen: 2')->assertSuccessful();

    expect(Seminar::query()->pluck('id')->sort()->values()->all())->toBe(collect([$young, $signed, $paid, $running])->pluck('id')->sort()->values()->all());
    Storage::disk('local')->assertMissing('seminars/'.$old->id.'/n.pdf');
});

it('points the coordination once to results links that expire within thirty days', function () {
    $coordination = rkUser(RoleKey::Coordination);
    $soon = Seminar::factory()->status(SeminarStatus::Completed)->create(['results_url' => 'https://cloud.example/s/a', 'results_expires_on' => now()->addDays(10)]);
    Seminar::factory()->status(SeminarStatus::Completed)->create(['results_url' => 'https://cloud.example/s/b', 'results_expires_on' => now()->addDays(90)]);

    $this->artisan('seminars:results-expiry', ['--dry-run' => true])->expectsOutputToContain('ablaufende Links: 1')->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('seminars:results-expiry')->expectsOutputToContain('ablaufende Links: 1')->assertSuccessful();

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($coordination->email) && str_starts_with($mail->subjectLine, 'Ergebnislink läuft bald ab'));
    expect($soon->fresh()->results_expiry_notified_at)->not->toBeNull();

    Mail::fake();
    $this->artisan('seminars:results-expiry')->expectsOutputToContain('ablaufende Links: 0')->assertSuccessful();
    Mail::assertNothingSent();
});
