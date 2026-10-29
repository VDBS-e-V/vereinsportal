<?php

use App\Modules\Access\Models\User;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\PartnerType;
use App\Modules\Partners\Models\Partner;
use App\Modules\People\Models\Person;

it('stores a school partner with typ-specific attributes', function () {
    $partner = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Gesamtschule Musterstadt',
        'status' => PartnerStatus::Active,
        'attributes' => [
            'school_type' => 'Gesamtschule',
            'student_count' => 650,
        ],
    ]);

    expect($partner->type)->toBe(PartnerType::School)
        ->and($partner->status)->toBe(PartnerStatus::Active)
        ->and($partner->attributes)->toBe([
            'school_type' => 'Gesamtschule',
            'student_count' => 650,
        ]);
});

it('has several contact persons, each free text or linked to a known person', function () {
    $person = Person::factory()->create();

    $partner = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Schule A',
    ]);

    $partner->contacts()->create(['name' => 'Max Mustermann', 'role' => 'Sekretariat', 'email' => 'max@schule-a.example']);
    $partner->contacts()->create(['name' => 'Erika Beispiel', 'role' => 'Schulleitung', 'person_id' => $person->id, 'is_primary' => true]);

    $contacts = $partner->contacts;

    expect($contacts->pluck('name')->all())->toBe(['Erika Beispiel', 'Max Mustermann'])
        ->and($contacts->first()->person->is($person))->toBeTrue()
        ->and($contacts->last()->person)->toBeNull()
        ->and($contacts->first()->is_primary)->toBeTrue();
});

it('defaults to active status', function () {
    $partner = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Schule C',
    ]);

    expect($partner->status)->toBe(PartnerStatus::Active);
});

it('tracks who created and last updated the partner record', function () {
    $staff = User::factory()->create();

    $partner = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Schule D',
        'created_by_user_id' => $staff->id,
        'updated_by_user_id' => $staff->id,
    ]);

    expect($partner->createdBy->is($staff))->toBeTrue()
        ->and($partner->updatedBy->is($staff))->toBeTrue();
});
