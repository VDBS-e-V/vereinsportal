<?php

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Schema;

it('uses the dedicated testing database', function () {
    expect(config('database.connections.mysql.database'))
        ->toBe('vdb_testing');
});

it('has the core identity foundation schema', function () {
    expect(Schema::hasTable('persons'))->toBeTrue()
        ->and(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('password_reset_tokens'))->toBeTrue()
        ->and(Schema::hasTable('sessions'))->toBeTrue();

    expect(Schema::hasColumns('persons', [
        'id',
        'first_name',
        'last_name',
        'birth_date',
        'email',
    ]))->toBeTrue();

    expect(Schema::hasColumns('users', [
        'id',
        'person_id',
        'email',
        'password',
        'status',
        'session_version',
        'anonymized_at',
        'anonymized_ref',
    ]))->toBeTrue();
});

it('links a person to exactly one user account', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'erika@example.test',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => 'erika@example.test',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    expect($person->user)
        ->toBeInstanceOf(User::class)
        ->and($person->user->is($user))
        ->toBeTrue();

    expect($user->person)
        ->toBeInstanceOf(Person::class)
        ->and($user->person->is($person))
        ->toBeTrue();
});

it('allows a user account without a linked person', function () {
    $user = User::query()->create([
        'person_id' => null,
        'email' => 'technical@example.test',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    expect($user->person_id)->toBeNull()
        ->and($user->person)->toBeNull();
});

it('enforces unique person emails at database level', function () {
    Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'duplicate@example.test',
        'country_code' => 'DE',
    ]);

    expect(fn () => Person::query()->create([
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'birth_date' => '1991-02-20',
        'email' => 'duplicate@example.test',
        'country_code' => 'DE',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('allows only one user account per person', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'person@example.test',
        'country_code' => 'DE',
    ]);

    User::query()->create([
        'person_id' => $person->id,
        'email' => 'first@example.test',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    expect(fn () => User::query()->create([
        'person_id' => $person->id,
        'email' => 'second@example.test',
        'password' => 'secret-password',
        'status' => 'active',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('prevents deleting a person while a user references it', function () {
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'birth_date' => '1990-01-15',
        'email' => 'referenced@example.test',
        'country_code' => 'DE',
    ]);

    User::query()->create([
        'person_id' => $person->id,
        'email' => 'account@example.test',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    expect(fn () => $person->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('casts the account status to the user status enum', function () {
    $user = User::query()->create([
        'email' => 'status@example.test',
        'password' => 'secret-password',
        'status' => \App\Modules\Identity\Enums\UserStatus::Active,
    ]);

    expect($user->status)
        ->toBe(\App\Modules\Identity\Enums\UserStatus::Active);

    expect($user->getRawOriginal('status'))
        ->toBe('active');
});