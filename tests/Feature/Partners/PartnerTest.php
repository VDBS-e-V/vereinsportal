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

it('can reference a known contact person or free-text contact details', function () {
    $person = Person::factory()->create();

    $withPerson = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Schule A',
        'contact_person_id' => $person->id,
    ]);

    $withFreeText = Partner::query()->create([
        'public_id' => (string) Illuminate\Support\Str::ulid(),
        'type' => PartnerType::School,
        'name' => 'Schule B',
        'contact_name' => 'Max Mustermann',
        'contact_email' => 'max@schule-b.example',
    ]);

    expect($withPerson->contactPerson->is($person))->toBeTrue()
        ->and($withFreeText->contactPerson)->toBeNull()
        ->and($withFreeText->contact_name)->toBe('Max Mustermann');
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
