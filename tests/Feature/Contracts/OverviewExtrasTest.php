<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contracts\Models\SeminarContract;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarSession;
use App\Support\TaskOverview;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function ovUser(RoleKey $role): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

it('shows counts of forms waiting for review and approval and of contracts waiting for the scan', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done]);
    SeminarSession::query()->create(['seminar_id' => $seminar->id, 'starts_at' => now()->subDays(3)->setTime(9, 0), 'ends_at' => now()->subDays(3)->setTime(15, 0), 'location' => 'Aula']);
    SeminarContract::query()->create(['number' => 'K-2026-0001', 'seminar_id' => $seminar->id, 'data' => ['name' => 'X'], 'status' => 'created']);

    $labels = fn (User $user): array => array_column(TaskOverview::for($user), 'label');

    expect($labels(ovUser(RoleKey::Coordination)))->toContain('Verträge warten auf den unterschriebenen Scan')
        ->and($labels(ovUser(RoleKey::Team)))->not->toContain('Verträge warten auf den unterschriebenen Scan');

    expect(FormApplication::query()->count())->toBe(0)
        ->and($labels(ovUser(RoleKey::AdministrationStaff)))->not->toContain('Anträge warten auf Prüfung');
});

it('shows the sums of forms on the seminar page only to those who may see forms and only numbers', function () {
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Scheduled]);
    FormApplication::query()->create(['number' => 'E-2026-0001', 'type' => 'expense', 'applicant_name' => 'Geheime Person', 'seminar_id' => $seminar->id, 'status' => 'submitted', 'total_cents' => 4500, 'purpose' => 'x']);

    $this->actingAs(ovUser(RoleKey::AdministrationStaff))->get(route('seminars.show', $seminar))->assertOk()->assertSee('Kostenerstattungen zu diesem Seminar')->assertSee('45,00')->assertDontSee('Geheime Person');
    $this->actingAs(ovUser(RoleKey::Coordination))->get(route('seminars.show', $seminar))->assertOk()->assertDontSee('Kostenerstattungen zu diesem Seminar');
});

it('mentions reimbursements and contracts in the default privacy text', function () {
    $this->get(route('pages.show', 'datenschutz'))->assertOk()->assertSee('Kostenerstattung und Beschaffung')->assertSee('Honorarverträge');
});
