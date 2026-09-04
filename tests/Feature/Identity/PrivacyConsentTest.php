<?php

use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\PrivacyConsent;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('stores privacy consent as an immutable historical record', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'privacy@example.test',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
    ]);

    $consent = PrivacyConsent::query()->create([
        'person_id' => $person->id,
        'user_id' => $user->id,
        'context' => 'registration',
        'notice_version' => '2026-08',
        'accepted' => true,
        'accepted_at' => now(),
    ]);

    expect($consent->accepted)->toBeTrue()
        ->and($consent->accepted_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($consent->person->is($person))
        ->toBeTrue()
        ->and($consent->user->is($user))
        ->toBeTrue();
});

it('can retain multiple consent records for the same person', function () {
    $person = Person::query()->create([
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'birth_date' => '1991-02-20',
        'email' => 'history@example.test',
        'country_code' => 'DE',
    ]);

    PrivacyConsent::query()->create([
        'person_id' => $person->id,
        'context' => 'registration',
        'notice_version' => '2026-08',
        'accepted' => true,
        'accepted_at' => now(),
    ]);

    PrivacyConsent::query()->create([
        'person_id' => $person->id,
        'context' => 'registration',
        'notice_version' => '2027-01',
        'accepted' => true,
        'accepted_at' => now()->addMinute(),
    ]);

    expect(
        PrivacyConsent::query()
            ->where('person_id', $person->id)
            ->count()
    )->toBe(2);
});

it('requires a created at timestamp at database level', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'privacy-not-null@example.test',
        'country_code' => 'DE',
    ]);

    expect(fn () => DB::table('privacy_consents')->insert([
        'person_id' => $person->id,
        'user_id' => null,
        'context' => 'registration',
        'notice_version' => 'v1',
        'accepted' => true,
        'accepted_at' => now(),
        'created_at' => null,
    ]))->toThrow(QueryException::class);
});
